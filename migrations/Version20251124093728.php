<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124093728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acl (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, user_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_BC806D12A76ED395 (user_id), INDEX IDX_BC806D12ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE actuator (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, date_start DATETIME NOT NULL, date_end DATETIME NOT NULL, number INT NOT NULL, status VARCHAR(255) NOT NULL, user_comment LONGTEXT DEFAULT NULL, confirm_comment LONGTEXT DEFAULT NULL, title LONGTEXT DEFAULT NULL, catalogue_resource_id INT NOT NULL, workflow_id INT DEFAULT NULL, INDEX IDX_E00CEDDED34CF4B5 (catalogue_resource_id), INDEX IDX_E00CEDDE2C7C2CBA (workflow_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE booking_user (booking_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_9502F4073301C60 (booking_id), INDEX IDX_9502F407A76ED395 (user_id), PRIMARY KEY (booking_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE booking_resource (booking_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_87A56A9B3301C60 (booking_id), INDEX IDX_87A56A9B89329D25 (resource_id), PRIMARY KEY (booking_id, resource_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE booking_custom_field (booking_id INT NOT NULL, custom_field_id INT NOT NULL, INDEX IDX_7A675CFE3301C60 (booking_id), INDEX IDX_7A675CFEA1E5E0D4 (custom_field_id), PRIMARY KEY (booking_id, custom_field_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE catalogue_resource (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, view VARCHAR(255) DEFAULT NULL, independent_options TINYINT(1) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, service_id INT NOT NULL, type_id INT DEFAULT NULL, sub_type_id INT DEFAULT NULL, actuator_id INT DEFAULT NULL, localization_id INT DEFAULT NULL, INDEX IDX_71052712ED5CA9E6 (service_id), INDEX IDX_71052712C54C8C93 (type_id), INDEX IDX_71052712BA94D067 (sub_type_id), INDEX IDX_7105271277D4D665 (actuator_id), INDEX IDX_710527126A2856C7 (localization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, view VARCHAR(255) DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category_custom_field (category_id INT NOT NULL, custom_field_id INT NOT NULL, INDEX IDX_422F9EA312469DE2 (category_id), INDEX IDX_422F9EA3A1E5E0D4 (custom_field_id), PRIMARY KEY (category_id, custom_field_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE custom_field (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, is_attribute TINYINT(1) NOT NULL, label VARCHAR(255) DEFAULT NULL, catalog_id INT DEFAULT NULL, INDEX IDX_98F8BD31CC3C66FC (catalog_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE custom_field_resource (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) DEFAULT NULL, custom_field_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_404C4601A1E5E0D4 (custom_field_id), INDEX IDX_404C460189329D25 (resource_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, provider VARCHAR(255) NOT NULL, query LONGTEXT NOT NULL, title VARCHAR(255) NOT NULL, users LONGTEXT DEFAULT NULL, service_id INT DEFAULT NULL, INDEX IDX_6DC044C5ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE group_provision (group_id INT NOT NULL, provision_id INT NOT NULL, INDEX IDX_7A281E71FE54D947 (group_id), INDEX IDX_7A281E713EC01A31 (provision_id), PRIMARY KEY (group_id, provision_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE localization (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, data JSON DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX IDX_98DC9F47727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE period (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, day JSON DEFAULT NULL, date_start DATE DEFAULT NULL, date_end DATE DEFAULT NULL, time_start TIME DEFAULT NULL, time_end TIME DEFAULT NULL, period_bracket_id INT NOT NULL, INDEX IDX_C5B81ECE6867E0FB (period_bracket_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE period_bracket (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, service_id INT NOT NULL, INDEX IDX_BAE78336ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE provision (id INT AUTO_INCREMENT NOT NULL, date_start DATE DEFAULT NULL, date_end DATE DEFAULT NULL, min_booking_time TIME DEFAULT NULL, max_booking_time TIME DEFAULT NULL, booking_interval INT DEFAULT NULL, max_booking_duration INT DEFAULT NULL, days JSON DEFAULT NULL, max_booking_by_day INT DEFAULT NULL, max_booking_by_week INT NOT NULL, allow_multiple_day TINYINT(1) DEFAULT NULL, workflow_id INT DEFAULT NULL, catalogue_resource_id INT DEFAULT NULL, period_bracket_id INT DEFAULT NULL, INDEX IDX_BA9B42902C7C2CBA (workflow_id), INDEX IDX_BA9B4290D34CF4B5 (catalogue_resource_id), INDEX IDX_BA9B42906867E0FB (period_bracket_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT NOT NULL, inventory_number VARCHAR(255) DEFAULT NULL, actuator_profile VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, additional_informations LONGTEXT DEFAULT NULL, service_id INT DEFAULT NULL, catalogue_resource_id INT NOT NULL, INDEX IDX_BC91F416ED5CA9E6 (service_id), INDEX IDX_BC91F416D34CF4B5 (catalogue_resource_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rule_resource (id INT AUTO_INCREMENT NOT NULL, arguments JSON NOT NULL, resource_id INT NOT NULL, rule_id INT NOT NULL, INDEX IDX_706D17BA89329D25 (resource_id), INDEX IDX_706D17BA744E0351 (rule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rules (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, method VARCHAR(255) NOT NULL, arguments JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT NOT NULL, type LONGTEXT NOT NULL, period_bracket_id INT DEFAULT NULL, INDEX IDX_E19D9AD26867E0FB (period_bracket_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE statistics (id INT AUTO_INCREMENT NOT NULL, author VARCHAR(255) NOT NULL, participants LONGTEXT DEFAULT NULL, date_start DATETIME NOT NULL, date_end DATETIME NOT NULL, catalog LONGTEXT DEFAULT NULL, resources LONGTEXT DEFAULT NULL, service LONGTEXT DEFAULT NULL, localization VARCHAR(255) NOT NULL, workflow LONGTEXT DEFAULT NULL, custom_field VARCHAR(255) DEFAULT NULL, booking LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(255) NOT NULL, display_user_name VARCHAR(255) NOT NULL, roles JSON NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workflow (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT NOT NULL, auto_validation TINYINT(1) DEFAULT NULL, auto_start TINYINT(1) NOT NULL, auto_end TINYINT(1) NOT NULL, configuration JSON DEFAULT NULL, service_id INT DEFAULT NULL, INDEX IDX_65C59816ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workflow_log (id INT AUTO_INCREMENT NOT NULL, status_target VARCHAR(255) NOT NULL, date DATETIME NOT NULL, comment LONGTEXT DEFAULT NULL, booking_id INT DEFAULT NULL, INDEX IDX_CF7326E83301C60 (booking_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE acl ADD CONSTRAINT FK_BC806D12A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE acl ADD CONSTRAINT FK_BC806D12ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDED34CF4B5 FOREIGN KEY (catalogue_resource_id) REFERENCES catalogue_resource (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE2C7C2CBA FOREIGN KEY (workflow_id) REFERENCES workflow (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE booking_user ADD CONSTRAINT FK_9502F4073301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_user ADD CONSTRAINT FK_9502F407A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_resource ADD CONSTRAINT FK_87A56A9B3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_resource ADD CONSTRAINT FK_87A56A9B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_custom_field ADD CONSTRAINT FK_7A675CFE3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking_custom_field ADD CONSTRAINT FK_7A675CFEA1E5E0D4 FOREIGN KEY (custom_field_id) REFERENCES custom_field (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE catalogue_resource ADD CONSTRAINT FK_71052712ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE catalogue_resource ADD CONSTRAINT FK_71052712C54C8C93 FOREIGN KEY (type_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE catalogue_resource ADD CONSTRAINT FK_71052712BA94D067 FOREIGN KEY (sub_type_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE catalogue_resource ADD CONSTRAINT FK_7105271277D4D665 FOREIGN KEY (actuator_id) REFERENCES actuator (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE catalogue_resource ADD CONSTRAINT FK_710527126A2856C7 FOREIGN KEY (localization_id) REFERENCES localization (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE category_custom_field ADD CONSTRAINT FK_422F9EA312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_custom_field ADD CONSTRAINT FK_422F9EA3A1E5E0D4 FOREIGN KEY (custom_field_id) REFERENCES custom_field (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_field ADD CONSTRAINT FK_98F8BD31CC3C66FC FOREIGN KEY (catalog_id) REFERENCES catalogue_resource (id)');
        $this->addSql('ALTER TABLE custom_field_resource ADD CONSTRAINT FK_404C4601A1E5E0D4 FOREIGN KEY (custom_field_id) REFERENCES custom_field (id)');
        $this->addSql('ALTER TABLE custom_field_resource ADD CONSTRAINT FK_404C460189329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C5ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE group_provision ADD CONSTRAINT FK_7A281E71FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_provision ADD CONSTRAINT FK_7A281E713EC01A31 FOREIGN KEY (provision_id) REFERENCES provision (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE localization ADD CONSTRAINT FK_98DC9F47727ACA70 FOREIGN KEY (parent_id) REFERENCES localization (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE6867E0FB FOREIGN KEY (period_bracket_id) REFERENCES period_bracket (id)');
        $this->addSql('ALTER TABLE period_bracket ADD CONSTRAINT FK_BAE78336ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE provision ADD CONSTRAINT FK_BA9B42902C7C2CBA FOREIGN KEY (workflow_id) REFERENCES workflow (id)');
        $this->addSql('ALTER TABLE provision ADD CONSTRAINT FK_BA9B4290D34CF4B5 FOREIGN KEY (catalogue_resource_id) REFERENCES catalogue_resource (id)');
        $this->addSql('ALTER TABLE provision ADD CONSTRAINT FK_BA9B42906867E0FB FOREIGN KEY (period_bracket_id) REFERENCES period_bracket (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416D34CF4B5 FOREIGN KEY (catalogue_resource_id) REFERENCES catalogue_resource (id)');
        $this->addSql('ALTER TABLE rule_resource ADD CONSTRAINT FK_706D17BA89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE rule_resource ADD CONSTRAINT FK_706D17BA744E0351 FOREIGN KEY (rule_id) REFERENCES rules (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD26867E0FB FOREIGN KEY (period_bracket_id) REFERENCES period_bracket (id)');
        $this->addSql('ALTER TABLE workflow ADD CONSTRAINT FK_65C59816ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE workflow_log ADD CONSTRAINT FK_CF7326E83301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acl DROP FOREIGN KEY FK_BC806D12A76ED395');
        $this->addSql('ALTER TABLE acl DROP FOREIGN KEY FK_BC806D12ED5CA9E6');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDED34CF4B5');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE2C7C2CBA');
        $this->addSql('ALTER TABLE booking_user DROP FOREIGN KEY FK_9502F4073301C60');
        $this->addSql('ALTER TABLE booking_user DROP FOREIGN KEY FK_9502F407A76ED395');
        $this->addSql('ALTER TABLE booking_resource DROP FOREIGN KEY FK_87A56A9B3301C60');
        $this->addSql('ALTER TABLE booking_resource DROP FOREIGN KEY FK_87A56A9B89329D25');
        $this->addSql('ALTER TABLE booking_custom_field DROP FOREIGN KEY FK_7A675CFE3301C60');
        $this->addSql('ALTER TABLE booking_custom_field DROP FOREIGN KEY FK_7A675CFEA1E5E0D4');
        $this->addSql('ALTER TABLE catalogue_resource DROP FOREIGN KEY FK_71052712ED5CA9E6');
        $this->addSql('ALTER TABLE catalogue_resource DROP FOREIGN KEY FK_71052712C54C8C93');
        $this->addSql('ALTER TABLE catalogue_resource DROP FOREIGN KEY FK_71052712BA94D067');
        $this->addSql('ALTER TABLE catalogue_resource DROP FOREIGN KEY FK_7105271277D4D665');
        $this->addSql('ALTER TABLE catalogue_resource DROP FOREIGN KEY FK_710527126A2856C7');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE category_custom_field DROP FOREIGN KEY FK_422F9EA312469DE2');
        $this->addSql('ALTER TABLE category_custom_field DROP FOREIGN KEY FK_422F9EA3A1E5E0D4');
        $this->addSql('ALTER TABLE custom_field DROP FOREIGN KEY FK_98F8BD31CC3C66FC');
        $this->addSql('ALTER TABLE custom_field_resource DROP FOREIGN KEY FK_404C4601A1E5E0D4');
        $this->addSql('ALTER TABLE custom_field_resource DROP FOREIGN KEY FK_404C460189329D25');
        $this->addSql('ALTER TABLE `group` DROP FOREIGN KEY FK_6DC044C5ED5CA9E6');
        $this->addSql('ALTER TABLE group_provision DROP FOREIGN KEY FK_7A281E71FE54D947');
        $this->addSql('ALTER TABLE group_provision DROP FOREIGN KEY FK_7A281E713EC01A31');
        $this->addSql('ALTER TABLE localization DROP FOREIGN KEY FK_98DC9F47727ACA70');
        $this->addSql('ALTER TABLE period DROP FOREIGN KEY FK_C5B81ECE6867E0FB');
        $this->addSql('ALTER TABLE period_bracket DROP FOREIGN KEY FK_BAE78336ED5CA9E6');
        $this->addSql('ALTER TABLE provision DROP FOREIGN KEY FK_BA9B42902C7C2CBA');
        $this->addSql('ALTER TABLE provision DROP FOREIGN KEY FK_BA9B4290D34CF4B5');
        $this->addSql('ALTER TABLE provision DROP FOREIGN KEY FK_BA9B42906867E0FB');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416ED5CA9E6');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416D34CF4B5');
        $this->addSql('ALTER TABLE rule_resource DROP FOREIGN KEY FK_706D17BA89329D25');
        $this->addSql('ALTER TABLE rule_resource DROP FOREIGN KEY FK_706D17BA744E0351');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD26867E0FB');
        $this->addSql('ALTER TABLE workflow DROP FOREIGN KEY FK_65C59816ED5CA9E6');
        $this->addSql('ALTER TABLE workflow_log DROP FOREIGN KEY FK_CF7326E83301C60');
        $this->addSql('DROP TABLE acl');
        $this->addSql('DROP TABLE actuator');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_user');
        $this->addSql('DROP TABLE booking_resource');
        $this->addSql('DROP TABLE booking_custom_field');
        $this->addSql('DROP TABLE catalogue_resource');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_custom_field');
        $this->addSql('DROP TABLE custom_field');
        $this->addSql('DROP TABLE custom_field_resource');
        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE group_provision');
        $this->addSql('DROP TABLE localization');
        $this->addSql('DROP TABLE period');
        $this->addSql('DROP TABLE period_bracket');
        $this->addSql('DROP TABLE provision');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE rule_resource');
        $this->addSql('DROP TABLE rules');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE statistics');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE workflow');
        $this->addSql('DROP TABLE workflow_log');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
