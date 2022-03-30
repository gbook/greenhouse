#include <stdio.h>
#include <unistd.h>
#include <stdint.h>
#include <stdlib.h>
#include <fstream>
#include <cstring>
#include <string>
#include <memory>
#include <mariadb/mysql.h>

#include "ds18b20.h"
#include "Timer.h"

/* global vars */
Timer timer;
DS18b20 ds18b20_thermo;
MYSQL *con;

float 	Temp1 	= 0.0;
float 	Temp2 	= 0.0;
float 	Temp3	= 0.0;
float 	Temp4 	= 0.0;
float 	Temp5 	= 0.0;

int numSensors = 0;

/* -------------------------------------------------- */
/* ----- SQLError ----------------------------------- */
/* -------------------------------------------------- */
void SQLError() {
	fprintf(stderr, "%s\n", mysql_error(con));
	mysql_close(con);
	exit(1);
}


/* -------------------------------------------------- */
/* ----- RecordTemps -------------------------------- */
/* -------------------------------------------------- */
void RecordTemps() {
	
	/* get the temperatures */
	ds18b20_thermo.DS_GetTemp(0,&Temp1);
	ds18b20_thermo.DS_GetTemp(1,&Temp2);
	ds18b20_thermo.DS_GetTemp(2,&Temp3);
	ds18b20_thermo.DS_GetTemp(3,&Temp4);
	ds18b20_thermo.DS_GetTemp(4,&Temp5);
	
	//printf("%.1fC %.1fC %.1fC %.1fC %.1fC\n",Temp1,Temp2,Temp3,Temp4,Temp5);

	/* write the temperatures to the database */
	char sql[2048];
	sprintf(sql, "insert into temps (sensor_id, temp_c) values (1, %.2f)", Temp1);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "insert into temps (sensor_id, temp_c) values (2, %.2f)", Temp2);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "insert into temps (sensor_id, temp_c) values (3, %.2f)", Temp3);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "insert into temps (sensor_id, temp_c) values (4, %.2f)", Temp4);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "insert into temps (sensor_id, temp_c) values (5, %.2f)", Temp5);
	if (mysql_query(con, sql))
		SQLError();
	
	/* update the recent temps for the tiny screen on the Pi */
	sprintf(sql, "update sensors set recent_temp = %.2f where sensor_id = 1", Temp1);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "update sensors set recent_temp = %.2f where sensor_id = 2", Temp2);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "update sensors set recent_temp = %.2f where sensor_id = 3", Temp3);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "update sensors set recent_temp = %.2f where sensor_id = 4", Temp4);
	if (mysql_query(con, sql))
		SQLError();

	sprintf(sql, "update sensors set recent_temp = %.2f where sensor_id = 5", Temp5);
	if (mysql_query(con, sql))
		SQLError();
	
}


/* -------------------------------------------------- */
/* ----- Setup -------------------------------------- */
/* -------------------------------------------------- */
void Setup() {
	printf("DS18b20 Setup...");
	ds18b20_thermo.DS_FindDevices();
	printf("Found %d DS18b20 devices\n", ds18b20_thermo.DS_get_num_devices());

	numSensors = ds18b20_thermo.DS_get_num_devices();

	if(ds18b20_thermo.DS_get_num_devices() > 0)
		ds18b20_thermo.DS_CheckDevices();
	else
		printf("No ds18b20 devices found\n");
	
	//timer.every(10000,RecordTemps,true);
}


/* -------------------------------------------------- */
/* ----- main --------------------------------------- */
/* -------------------------------------------------- */
int main(int argc, const char *argv[]) {
	printf("Waiting 10s to try to connect to MariaDB");
	sleep(10); /* allow mariadb a chance to start */
	printf("Done waiting to connect to MariaDB");
	
	con = mysql_init(NULL);

	if (con == NULL) {
		fprintf(stderr, "%s\n", mysql_error(con));
		exit(1);
	}
	
	printf("Mariadb client version: %s\n", mysql_get_client_info());

	if (mysql_real_connect(con, "localhost", "root", "password", "greenhouse", 3306, NULL, 0) == NULL)
		SQLError();
	else
		printf("Connected to database\n");

    Setup();
	
	while (1) {		
		//timer.update();
		RecordTemps();
		sleep(30);
	}
	
	mysql_close(con);
	
	return 0;
}

