#include <stdio.h>
#include "pico/stdlib.h"
#include "hardware/i2c.h"
#include "hardware/uart.h"
#include "OLEDDisplay.h"
#include "DeskAPI.h"

#define I2C_PORT i2c0
#define I2C_SDA 4
#define I2C_SCL 5
#define OLED_ADDR 0x3C
#define UART_ID uart0

int main()
{
    stdio_init_all();
    
    i2c_init(I2C_PORT, 400 * 1000);
    gpio_set_function(I2C_SDA, GPIO_FUNC_I2C);
    gpio_set_function(I2C_SCL, GPIO_FUNC_I2C);
    gpio_pull_up(I2C_SDA);
    gpio_pull_up(I2C_SCL);
    
    OLEDDisplay display(I2C_PORT, OLED_ADDR);
    display.init();
    
    DeskAPI api(UART_ID);
    api.init();
    
    printf("DeskUp Height Monitor Starting...\n");
    display.displayText("DeskUp", 0);
    display.displayText("Waiting...", 2);
    
    int lastHeight = -1;
    bool wasLoggedIn = false;
    
    while (true) {
        api.checkForUpdates();
        bool isLoggedIn = api.isUserLoggedIn();
        
        if (isLoggedIn != wasLoggedIn) {
            wasLoggedIn = isLoggedIn;
            display.clear();
            display.displayText("DeskUp", 0);
            
            if (isLoggedIn) {
                printf("User detected: %d\n", api.getCurrentUserId());
                display.displayText("User Online", 1);
                sleep_ms(1000);
            } else {
                printf("User logged out\n");
                display.displayText("Waiting...", 2);
                lastHeight = -1;
            }
        }
        
        if (isLoggedIn) {
            int currentHeight = api.getCurrentHeight();
            if (currentHeight != lastHeight) {
                printf("Height: %d cm\n", currentHeight);
                display.displayHeight(currentHeight);
                lastHeight = currentHeight;
            }
        }
        
        sleep_ms(100);
    }
}
