<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406202238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate Invoice id from int to UUID';
    }

    public function up(Schema $schema): void
    {
        // 1. Сначала дропаем constraint по точному имени из ошибки
        $this->addSql('ALTER TABLE invoice_items DROP CONSTRAINT fk_dcc4b9f82989f1fd');

        // 2. Очищаем таблицы
        $this->addSql('TRUNCATE TABLE invoice_items');
        $this->addSql('TRUNCATE TABLE invoices');

        // 3. Меняем тип id в invoices
        $this->addSql('ALTER TABLE invoices DROP COLUMN id');
        $this->addSql('ALTER TABLE invoices ADD COLUMN id UUID NOT NULL DEFAULT gen_random_uuid()');
        $this->addSql('ALTER TABLE invoices ADD PRIMARY KEY (id)');

        // 4. Меняем тип invoice_id в invoice_items
        $this->addSql('ALTER TABLE invoice_items DROP COLUMN invoice_id');
        $this->addSql('ALTER TABLE invoice_items ADD COLUMN invoice_id UUID NOT NULL');

        // 5. Восстанавливаем внешний ключ
        $this->addSql('ALTER TABLE invoice_items ADD CONSTRAINT fk_dcc4b9f82989f1fd FOREIGN KEY (invoice_id) REFERENCES invoices (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // 6. Убираем дефолт
        $this->addSql('ALTER TABLE invoices ALTER COLUMN id DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice_items DROP CONSTRAINT IF EXISTS fk_invoice_items_invoice_id');
        $this->addSql('TRUNCATE TABLE invoice_items CASCADE');
        $this->addSql('TRUNCATE TABLE invoices CASCADE');
        $this->addSql('ALTER TABLE invoices DROP COLUMN id');
        $this->addSql('ALTER TABLE invoices ADD COLUMN id SERIAL PRIMARY KEY');
        $this->addSql('ALTER TABLE invoice_items DROP COLUMN invoice_id');
        $this->addSql('ALTER TABLE invoice_items ADD COLUMN invoice_id INT NOT NULL');
        $this->addSql('ALTER TABLE invoice_items ADD CONSTRAINT fk_invoice_items_invoice_id FOREIGN KEY (invoice_id) REFERENCES invoices (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}