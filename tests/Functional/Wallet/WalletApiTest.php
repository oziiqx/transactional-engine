<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Response;

/**
 * @return array{0: non-empty-string, 1: array<string, mixed>}
 */
function createWallet(string $currency = 'EUR'): array
{
    $response = test()->jsonRequest('POST', '/api/wallets', [
        'holderId' => uuid(),
        'currency' => $currency,
    ]);

    expect($response->getStatusCode())->toBe(Response::HTTP_CREATED);
    $body = test()->decode($response);

    /** @var non-empty-string $id */
    $id = $body['id'];

    return [$id, $body];
}

it('opens a wallet with a zero balance', function (): void {
    [$id, $body] = createWallet('USD');

    expect($id)->not->toBeEmpty()
        ->and($body['currency'])->toBe('USD')
        ->and($body['balance'])->toBe('0.00')
        ->and($body['status'])->toBe('active');
});

it('returns 404 problem+json for an unknown wallet', function (): void {
    $response = $this->jsonRequest('GET', '/api/wallets/' . uuid());

    expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
        ->and($response->headers->get('Content-Type'))->toContain('application/problem+json');

    expect($this->decode($response))
        ->toMatchArray([
            'status' => 404,
            'code' => 'resource.not_found',
        ]);
});

it('credits a wallet and reflects the new balance', function (): void {
    [$id] = createWallet();

    $response = $this->jsonRequest('POST', "/api/wallets/{$id}/credits", [
        'amount' => '250.00',
        'currency' => 'EUR',
        'reason' => 'deposit',
    ]);

    expect($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($this->decode($response)['balance'])->toBe('250.00');
});

it('rejects an overdrawing debit with 422 and a domain error code', function (): void {
    [$id] = createWallet();
    $this->jsonRequest('POST', "/api/wallets/{$id}/credits", [
        'amount' => '10.00',
        'currency' => 'EUR',
        'reason' => 'deposit',
    ]);

    $response = $this->jsonRequest('POST', "/api/wallets/{$id}/debits", [
        'amount' => '999.00',
        'currency' => 'EUR',
        'reason' => 'withdrawal',
    ]);

    expect($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->and($this->decode($response)['code'])->toBe('wallet.insufficient_funds');
});

it('lists ledger history newest-first', function (): void {
    [$id] = createWallet();
    foreach (['10.00', '20.00', '30.00'] as $amount) {
        $this->jsonRequest('POST', "/api/wallets/{$id}/credits", [
            'amount' => $amount,
            'currency' => 'EUR',
            'reason' => 'deposit',
        ]);
    }

    $response = $this->jsonRequest('GET', "/api/wallets/{$id}/ledger-entries?limit=2");
    $body = $this->decode($response);

    expect($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($body['items'])->toHaveCount(2)
        ->and($body['items'][0]['sequence'])->toBe(3)
        ->and($body['hasMore'])->toBeTrue()
        ->and($body['nextCursor'])->not->toBeNull();
});

it('freezes a wallet and then blocks movement', function (): void {
    [$id] = createWallet();

    $this->jsonRequest('POST', "/api/wallets/{$id}/transitions", [
        'action' => 'freeze',
    ]);

    $blocked = $this->jsonRequest('POST', "/api/wallets/{$id}/credits", [
        'amount' => '1.00',
        'currency' => 'EUR',
        'reason' => 'deposit',
    ]);

    expect($blocked->getStatusCode())->toBe(Response::HTTP_CONFLICT)
        ->and($this->decode($blocked)['code'])->toBe('wallet.not_active');
});
