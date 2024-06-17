#include <stdio.h>
#include <wiringPi.h>
#include <curl/curl.h>
#include <wiringPiSPI.h>
#include <unistd.h>
#define SPI_CHANNEL 0
#define SPI_SPEED 1000000

int read_adc(int channel){
	unsigned char buffer[3];
	buffer[0]=1;
	buffer[1]=(8+channel)<<4;
	buffer[2]=0;

	wiringPiSPIDataRW(SPI_CHANNEL, buffer,3);

	int result  = ((buffer[1] &3)<<8) + buffer[2];
		
	

		
	return result;
}

void send_data_to_server(int value) {
    CURL *curl;
    CURLcode res;

    char postdata[100];
    snprintf(postdata, sizeof(postdata), "sensor_type=ppa800&value=%d", value);
   printf("%d\n",value);
    curl_global_init(CURL_GLOBAL_ALL);
    curl = curl_easy_init();

    if(curl) {
        curl_easy_setopt(curl, CURLOPT_URL, "http://223.130.162.254/recive_soildata.php");
        curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postdata);

        res = curl_easy_perform(curl);

        if(res != CURLE_OK) {
            fprintf(stderr, "curl_easy_perform() failed: %s\n", curl_easy_strerror(res));
        }

        curl_easy_cleanup(curl);
    }

    curl_global_cleanup();
    
 }

int main(void) {
    if (wiringPiSetup() == -1) {
        printf("WiringPi 초기화 실패\n");
        return 1;
    }
    if(wiringPiSPISetup(SPI_CHANNEL,SPI_SPEED)==-1){
	    printf("SPI setup faild\n");
	    return 1;
    }  


	   






  //  pinMode(SENSOR_PIN, INPUT);

    
       
	while(1){ 
        int sensor_value = read_adc(4);
	
      sensor_value =sensor_value*10;
        if (sensor_value >=0) {
            printf("Sensor:%d\n",sensor_value);
	    if(sensor_value>10){
            send_data_to_server(sensor_value);
	return 0;    }
        } else {
            printf("Sensor: Soil is dry\n");
        }

        
    

    
	}
return 0;
}

