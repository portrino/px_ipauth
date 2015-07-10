#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_pxipauth_ip_list tinytext NOT NULL,
	tx_pxipauth_mode tinyint(3) unsigned DEFAULT '0' NOT NULL
);