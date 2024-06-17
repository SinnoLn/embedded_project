#include <stdio.h>
#include <curl/curl.h> // cURL 라이브러리 추가
#include <wiringPiI2C.h>
#include <wiringPi.h>
#include <math.h>
#define Device_Address 0x68	

#define PWR_MGMT_1   0x6B
#define SMPLRT_DIV   0x19
#define CONFIG       0x1A
#define GYRO_CONFIG  0x1B
#define INT_ENABLE   0x38
#define ACCEL_XOUT_H 0x3B
#define ACCEL_YOUT_H 0x3D
#define ACCEL_ZOUT_H 0x3F
#define GYRO_XOUT_H  0x43
#define GYRO_YOUT_H  0x45
#define GYRO_ZOUT_H  0x47

int fd;

void MPU6050_Init(){
    wiringPiI2CWriteReg8 (fd, SMPLRT_DIV, 0x07);   /* Write to sample rate register */
    wiringPiI2CWriteReg8 (fd, PWR_MGMT_1, 0x01);   /* Write to power management register */
    wiringPiI2CWriteReg8 (fd, CONFIG, 0);       /* Write to Configuration register */
    wiringPiI2CWriteReg8 (fd, GYRO_CONFIG, 24);   /* Write to Gyro Configuration register */
    wiringPiI2CWriteReg8 (fd, INT_ENABLE, 0x01);   /*Write to interrupt enable register */
}

short read_raw_data(int addr){
    short high_byte,low_byte,value;
    high_byte = wiringPiI2CReadReg8(fd, addr);
    low_byte = wiringPiI2CReadReg8(fd, addr+1);
    value = (high_byte << 8) | low_byte;
    return value;
}

void ms_delay(int val){
    int i,j;
    for(i=0;i<=val;i++)
        for(j=0;j<1200;j++);
}
void calculate_inclination(float ax,float ay,float az, float *roll, float* pitch){
	*roll = atan2(ay,az)*180/M_PI;
	*pitch = atan2(-ax,sqrt(ay*ay+az*az))/M_PI;
}
float calculate_total_inclination(float roll, float pitch){
	return sqrt(roll * roll + pitch * pitch);
}
int main(){
    float Acc_x, Acc_y, Acc_z;
    float Ax=0, Ay=0, Az=0;
    fd = wiringPiI2CSetup(Device_Address);   /*Initializes I2C with device Address*/
    MPU6050_Init();                      /* Initializes MPU6050 */
    
   // while1)
   // {
        /*Read raw value of Accelerometer from MPU6050*/
        Acc_x = read_raw_data(ACCEL_XOUT_H);
        Acc_y = read_raw_data(ACCEL_YOUT_H);
        Acc_z = read_raw_data(ACCEL_ZOUT_H);
        
        /* Divide raw value by sensitivity scale factor */
        Ax = Acc_x/16384.0;
        Ay = Acc_y/16384.0;
        Az = Acc_z/16384.0;
        float roll,pitch;
	calculate_inclination(Ax,Ay,Az,&roll,&pitch);
	printf("Roll: %.2f, Pitch: %.2f\n",roll,pitch);
	float inclination = calculate_total_inclination(roll,pitch);
	printf("inclination %f\n", inclination);

        // Send data to server using cURL
        CURL *curl;
        CURLcode res;
        char postdata[100];
        snprintf(postdata, sizeof(postdata), "sensor_type=mpu6050&ax=%.3f&ay=%.3f&az=%.3f&incli=%.3f", Ax, Ay, Az,inclination);

        curl_global_init(CURL_GLOBAL_ALL);
        curl = curl_easy_init();

        if(curl) {
            curl_easy_setopt(curl, CURLOPT_URL, "http://223.130.162.254/receive_mpudata.php");
            curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postdata);

            res = curl_easy_perform(curl);

            if(res != CURLE_OK) {
                fprintf(stderr, "curl_easy_perform() failed: %s\n", curl_easy_strerror(res));
            }

            curl_easy_cleanup(curl);
       }        

        curl_global_cleanup();

        // Print data to console
        printf("\nAx=%.3f g\tAy=%.3f g\tAz=%.3f g\n", Ax, Ay, Az);
        delay(500);
        
   // }
    return 0;
}

