<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428135934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE holiday (id SERIAL NOT NULL, users_id INT DEFAULT NULL, type_id INT DEFAULT NULL, date_start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, date_demande TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, observation TEXT DEFAULT NULL, date_validation TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, nb_total_days DOUBLE PRECISION DEFAULT NULL, half_holiday_afternoon_start BOOLEAN DEFAULT NULL, half_holiday_morning_end BOOLEAN DEFAULT NULL, half_holiday_single VARCHAR(50) DEFAULT NULL, administration BOOLEAN DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DC9AB23467B3B43D ON holiday (users_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DC9AB234C54C8C93 ON holiday (type_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE holiday_type (id SERIAL NOT NULL, name VARCHAR(50) DEFAULT NULL, description VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_era_time (id SERIAL NOT NULL, superior_id INT DEFAULT NULL, superior2_id INT DEFAULT NULL, superior3_id INT DEFAULT NULL, name VARCHAR(50) DEFAULT NULL, first_name VARCHAR(50) DEFAULT NULL, email VARCHAR(50) DEFAULT NULL, login VARCHAR(50) DEFAULT NULL, password VARCHAR(100) DEFAULT NULL, last_activity_date VARCHAR(50) NOT NULL, active BOOLEAN DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, category JSON DEFAULT NULL, roles JSON DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5168970863D7ADF1 ON user_era_time (superior_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_51689708E00975A ON user_era_time (superior2_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_51689708B6BCF03F ON user_era_time (superior3_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE holiday ADD CONSTRAINT FK_DC9AB23467B3B43D FOREIGN KEY (users_id) REFERENCES user_era_time (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE holiday ADD CONSTRAINT FK_DC9AB234C54C8C93 FOREIGN KEY (type_id) REFERENCES holiday_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_era_time ADD CONSTRAINT FK_5168970863D7ADF1 FOREIGN KEY (superior_id) REFERENCES user_era_time (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_era_time ADD CONSTRAINT FK_51689708E00975A FOREIGN KEY (superior2_id) REFERENCES user_era_time (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_era_time ADD CONSTRAINT FK_51689708B6BCF03F FOREIGN KEY (superior3_id) REFERENCES user_era_time (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE holiday DROP CONSTRAINT FK_DC9AB23467B3B43D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE holiday DROP CONSTRAINT FK_DC9AB234C54C8C93
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_era_time DROP CONSTRAINT FK_5168970863D7ADF1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_era_time DROP CONSTRAINT FK_51689708E00975A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_era_time DROP CONSTRAINT FK_51689708B6BCF03F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE holiday
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE holiday_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_era_time
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
