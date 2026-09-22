CREATE TABLE IF NOT EXISTS wp_score_ticker_matches (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  match_date date NOT NULL,
  team_1_name varchar(64) NOT NULL,
  team_1_score smallint NOT NULL,
  team_2_name varchar(64) NOT NULL,
  team_2_score smallint NOT NULL,
  PRIMARY KEY (id),
  KEY match_date (match_date)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
