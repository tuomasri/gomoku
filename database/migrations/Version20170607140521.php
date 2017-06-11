<?php

namespace Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20170607140521 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, winner_id INT DEFAULT NULL, state SMALLINT NOT NULL, INDEX IDX_FF232B315DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_move (id INT AUTO_INCREMENT NOT NULL, game_id INT DEFAULT NULL, player_id INT DEFAULT NULL, x INT NOT NULL, y INT NOT NULL, INDEX IDX_770C71BDE48FD905 (game_id), INDEX IDX_770C71BD99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, game_id INT DEFAULT NULL, color SMALLINT NOT NULL, INDEX IDX_264E43A6E48FD905 (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_FF232B315DFCD4B8 FOREIGN KEY (winner_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE game_move ADD CONSTRAINT FK_770C71BDE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_move ADD CONSTRAINT FK_770C71BD99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_264E43A6E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE game_move DROP FOREIGN KEY FK_770C71BDE48FD905');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_264E43A6E48FD905');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_FF232B315DFCD4B8');
        $this->addSql('ALTER TABLE game_move DROP FOREIGN KEY FK_770C71BD99E6F5DF');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_move');
        $this->addSql('DROP TABLE player');
    }
}
