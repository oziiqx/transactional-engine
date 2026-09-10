<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Wallet context: wallets and their append-only ledger.
 */
final class Version20260910120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the wallets and wallet_ledger_entries tables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->platform instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE wallets (
                id                UUID         NOT NULL,
                holder_id         UUID         NOT NULL,
                currency          CHAR(3)      NOT NULL,
                status            VARCHAR(16)  NOT NULL,
                last_sequence     INT          NOT NULL,
                version           INT          NOT NULL DEFAULT 1,
                opened_at         TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                closed_at         TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                balance_minor     BIGINT       NOT NULL,
                balance_currency  CHAR(3)      NOT NULL,
                PRIMARY KEY (id)
            )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_wallet_holder_currency ON wallets (holder_id, currency)');
        $this->addSql('CREATE INDEX idx_wallet_status ON wallets (status)');

        $this->addSql(<<<'SQL'
            CREATE TABLE wallet_ledger_entries (
                id                       UUID         NOT NULL,
                wallet_id                UUID         NOT NULL,
                sequence                 INT          NOT NULL,
                direction                VARCHAR(8)   NOT NULL,
                reason                   VARCHAR(32)  NOT NULL,
                reference                VARCHAR(255) DEFAULT NULL,
                idempotency_key          VARCHAR(255) DEFAULT NULL,
                recorded_at              TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                amount_minor             BIGINT       NOT NULL,
                amount_currency          CHAR(3)      NOT NULL,
                balance_after_minor      BIGINT       NOT NULL,
                balance_after_currency   CHAR(3)      NOT NULL,
                PRIMARY KEY (id)
            )
            SQL);

        // Gap-free per-wallet sequence: this unique index is also the optimistic
        // guard against two concurrent movements racing on the same wallet.
        $this->addSql('CREATE UNIQUE INDEX uniq_ledger_wallet_sequence ON wallet_ledger_entries (wallet_id, sequence)');
        $this->addSql('CREATE INDEX idx_ledger_wallet_recorded ON wallet_ledger_entries (wallet_id, recorded_at, sequence)');
        $this->addSql('CREATE INDEX idx_ledger_wallet_idempotency ON wallet_ledger_entries (wallet_id, idempotency_key)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->platform instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('DROP TABLE wallet_ledger_entries');
        $this->addSql('DROP TABLE wallets');
    }
}
