CREATE TABLE tx_view_tracker_count (
	page int(11),
	pagetype int(11) DEFAULT 0,
	timestamp int(11),
	language varchar(10) DEFAULT '' NOT NULL,
	browser varchar(50) DEFAULT '' NOT NULL,
	os varchar(50) DEFAULT '' NOT NULL,
	device_type varchar(20) DEFAULT '' NOT NULL,
	country varchar(10) DEFAULT '' NOT NULL,
	KEY page (page),
	KEY page_time (page,timestamp),
	KEY page_type (page,pagetype),
	KEY timestamp (timestamp),
	KEY language (language),
	KEY browser (browser),
	KEY os (os),
	KEY device_type (device_type)
);
