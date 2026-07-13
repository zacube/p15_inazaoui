<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713121239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA76ED395');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ALTER must_change_password DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER must_change_password SET DEFAULT false');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT fk_6a2ca10ca76ed395');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT fk_6a2ca10ca76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
