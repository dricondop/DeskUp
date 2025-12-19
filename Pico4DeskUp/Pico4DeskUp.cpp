#include <stdio.h>
#include <cstring>
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
    display.displayText("DeskUp Monitor", 0);
    display.displayText("Waiting for", 2);
    display.displayText("user login...", 3);
    
    int lastHeight = -1;
    int lastDesk = -1;
    bool wasLoggedIn = false;
    char lastUserName[64] = "";
    
    while (true) {
        api.checkForUpdates();
        bool isLoggedIn = api.isUserLoggedIn();
        
        if (isLoggedIn != wasLoggedIn) {
            wasLoggedIn = isLoggedIn;
            display.clear();
            
            if (isLoggedIn) {
                const char* userName = api.getCurrentUserName();
                printf("User detected: %s (ID: %d)\n", userName, api.getCurrentUserId());
                
                display.displayText("DeskUp Active", 0);
                display.displayText("User:", 1);
                display.displayText(userName, 2);
                strcpy(lastUserName, userName);
                sleep_ms(2000);
            } else {
                printf("User logged out\n");
                display.displayText("DeskUp Monitor", 0);
                display.displayText("Waiting for", 2);
                display.displayText("user login...", 3);
                lastHeight = -1;
                lastDesk = -1;
                lastUserName[0] = '\0';
            }
        }
        
        if (isLoggedIn) {
            int currentHeight = api.getCurrentHeight();
            int currentDesk = api.getCurrentDeskNumber();
            const char* userName = api.getCurrentUserName();
            
            // Update display if any value changed
            if (currentHeight != lastHeight || currentDesk != lastDesk || strcmp(userName, lastUserName) != 0) {
                printf("Updating display - Desk: %d, Height: %d cm, User: %s\n", currentDesk, currentHeight, userName);
                
                display.clear();
                
                // Line 0: Title
                display.displayText("DeskUp Active", 0);
                
                // Line 1: User name
                char userLine[32];
                snprintf(userLine, sizeof(userLine), "User: %s", userName);
                display.displayText(userLine, 1);
                
                // Line 2: Desk number
                if (currentDesk > 0) {
                    char deskLine[32];
                    snprintf(deskLine, sizeof(deskLine), "Desk: %d", currentDesk);
                    display.displayText(deskLine, 2);
                }
                
                // Line 3: Current height
                char heightLine[32];
                snprintf(heightLine, sizeof(heightLine), "Height: %d cm", currentHeight);
                display.displayText(heightLine, 3);
                
                lastHeight = currentHeight;
                lastDesk = currentDesk;
                strcpy(lastUserName, userName);
            }
        }
        
        sleep_ms(100);
    }
}
