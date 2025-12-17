#include "DeskAPI.h"
#include <stdio.h>
#include <string.h>
#include <stdlib.h>

DeskAPI::DeskAPI(uart_inst_t* uart_instance) 
    : uart(uart_instance), currentUserId(0), currentHeight(110), currentDeskNumber(0), bufferPos(0) {
    memset(receiveBuffer, 0, sizeof(receiveBuffer));
    memset(currentUserName, 0, sizeof(currentUserName));
    strcpy(currentUserName, "None");
}

void DeskAPI::init() {
    uart_init(uart, 115200);
    gpio_set_function(0, GPIO_FUNC_UART);
    gpio_set_function(1, GPIO_FUNC_UART);
    printf("UART initialized for DeskUp communication\n");
}

bool DeskAPI::readLine() {
    while (uart_is_readable(uart)) {
        char c = uart_getc(uart);
        
        if (c == '\n' || c == '\r') {
            if (bufferPos > 0) {
                receiveBuffer[bufferPos] = '\0';
                bufferPos = 0;
                return true;
            }
        } else if (bufferPos < 255) {
            receiveBuffer[bufferPos++] = c;
        }
    }
    return false;
}

void DeskAPI::parseMessage(const char* message) {
    if (strncmp(message, "LOGIN:", 6) == 0) {
        currentUserId = atoi(message + 6);
        printf("User logged in: %d\n", currentUserId);
        return;
    }
    
    if (strncmp(message, "USER:", 5) == 0) {
        strncpy(currentUserName, message + 5, sizeof(currentUserName) - 1);
        currentUserName[sizeof(currentUserName) - 1] = '\0';
        printf("User name: %s\n", currentUserName);
        return;
    }
    
    if (strncmp(message, "LOGOUT", 6) == 0) {
        currentUserId = 0;
        currentDeskNumber = 0;
        strcpy(currentUserName, "None");
        printf("User logged out\n");
        return;
    }
    
    if (strncmp(message, "HEIGHT:", 7) == 0) {
        currentHeight = atoi(message + 7);
        printf("Height updated: %d cm\n", currentHeight);
        return;
    }
    
    if (strncmp(message, "DESK:", 5) == 0) {
        currentDeskNumber = atoi(message + 5);
        printf("Desk number: %d\n", currentDeskNumber);
        return;
    }
}

bool DeskAPI::checkForUpdates() {
    bool hasNewData = readLine();
    if (hasNewData) {
        parseMessage(receiveBuffer);
    }
    return hasNewData;
}

int DeskAPI::getCurrentUserId() {
    return currentUserId;
}

int DeskAPI::getCurrentHeight() {
    return currentHeight;
}

int DeskAPI::getCurrentDeskNumber() {
    return currentDeskNumber;
}

const char* DeskAPI::getCurrentUserName() {
    return currentUserName;
}

bool DeskAPI::isUserLoggedIn() {
    return currentUserId > 0;
}
