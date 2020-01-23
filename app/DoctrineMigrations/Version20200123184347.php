<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200123184347 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE restaurant_restaurant_category (restaurant_id INT NOT NULL, restaurant_category_id INT NOT NULL, PRIMARY KEY(restaurant_id, restaurant_category_id))');
        $this->addSql('CREATE INDEX IDX_A3171BA8B1E7706E ON restaurant_restaurant_category (restaurant_id)');
        $this->addSql('CREATE INDEX IDX_A3171BA8433DA7F8 ON restaurant_restaurant_category (restaurant_category_id)');
        $this->addSql('ALTER TABLE restaurant_restaurant_category ADD CONSTRAINT FK_A3171BA8B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant_restaurant_category ADD CONSTRAINT FK_A3171BA8433DA7F8 FOREIGN KEY (restaurant_category_id) REFERENCES restaurant_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant DROP CONSTRAINT fk_eb95123f12469de2');
        $this->addSql('DROP INDEX idx_eb95123f12469de2');
        $this->addSql('ALTER TABLE restaurant DROP category_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE restaurant_restaurant_category');
        $this->addSql('ALTER TABLE restaurant ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE restaurant ADD CONSTRAINT fk_eb95123f12469de2 FOREIGN KEY (category_id) REFERENCES restaurant_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_eb95123f12469de2 ON restaurant (category_id)');
    }
}
