-- SQL definition for module ticketsup
-- Copyright (C) 2013  Jean-François FERRY <jfefe@aternatik.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

CREATE TABLE IF NOT EXISTS llx_payplug_payment
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	payment_id    varchar(128) NOT NULL,
  origin_id    varchar(128) NOT NULL,
	fk_soc		integer DEFAULT 0,
	fk_propal	integer DEFAULT 0,
  fk_order		integer DEFAULT 0,
	fk_invoice	integer DEFAULT 0,
	last4 varchar(32),
	exp_month varchar(32),
	exp_year varchar(32),
	brand varchar(32),
  card_id varchar(128),
	fk_country varchar(32),
	tms timestamp
)ENGINE=innodb;