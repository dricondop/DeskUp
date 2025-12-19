#include "OLEDDisplay.h"
#include "font5x8.h"
#include <stdio.h>
#include <string.h>

#define SSD1306_SETCONTRAST 0x81
#define SSD1306_DISPLAYALLON_RESUME 0xA4
#define SSD1306_NORMALDISPLAY 0xA6
#define SSD1306_DISPLAYOFF 0xAE
#define SSD1306_DISPLAYON 0xAF
#define SSD1306_SETDISPLAYOFFSET 0xD3
#define SSD1306_SETCOMPINS 0xDA
#define SSD1306_SETVCOMDETECT 0xDB
#define SSD1306_SETDISPLAYCLOCKDIV 0xD5
#define SSD1306_SETPRECHARGE 0xD9
#define SSD1306_SETMULTIPLEX 0xA8
#define SSD1306_SETSTARTLINE 0x40
#define SSD1306_MEMORYMODE 0x20
#define SSD1306_COLUMNADDR 0x21
#define SSD1306_PAGEADDR 0x22
#define SSD1306_COMSCANINC 0xC0
#define SSD1306_COMSCANDEC 0xC8
#define SSD1306_SEGREMAP 0xA0
#define SSD1306_CHARGEPUMP 0x8D

OLEDDisplay::OLEDDisplay(i2c_inst_t* i2c_instance, uint8_t i2c_address) 
    : i2c(i2c_instance), address(i2c_address) {
}

void OLEDDisplay::sendCommand(uint8_t command) {
    uint8_t data[2] = {0x00, command};
    i2c_write_blocking(i2c, address, data, 2, false);
}

void OLEDDisplay::sendData(uint8_t* data, size_t length) {
    uint8_t buffer[length + 1];
    buffer[0] = 0x40;
    memcpy(buffer + 1, data, length);
    i2c_write_blocking(i2c, address, buffer, length + 1, false);
}

void OLEDDisplay::init() {
    const uint8_t initSequence[] = {
        SSD1306_DISPLAYOFF,
        SSD1306_SETDISPLAYCLOCKDIV, 0x80,
        SSD1306_SETMULTIPLEX, 0x3F,
        SSD1306_SETDISPLAYOFFSET, 0x00,
        SSD1306_SETSTARTLINE | 0x00,
        SSD1306_CHARGEPUMP, 0x14,
        SSD1306_MEMORYMODE, 0x00,
        SSD1306_SEGREMAP | 0x01,
        SSD1306_COMSCANDEC,
        SSD1306_SETCOMPINS, 0x12,
        SSD1306_SETCONTRAST, 0xCF,
        SSD1306_SETPRECHARGE, 0xF1,
        SSD1306_SETVCOMDETECT, 0x40,
        SSD1306_DISPLAYALLON_RESUME,
        SSD1306_NORMALDISPLAY,
        SSD1306_DISPLAYON
    };
    
    for (uint8_t cmd : initSequence) {
        sendCommand(cmd);
    }
    clear();
}

void OLEDDisplay::clear() {
    const uint8_t setupCommands[] = {
        SSD1306_COLUMNADDR, 0, 127,
        SSD1306_PAGEADDR, 0, 7
    };
    
    for (uint8_t cmd : setupCommands) {
        sendCommand(cmd);
    }
    
    uint8_t zeros[128] = {0};
    for (uint8_t i = 0; i < 8; i++) {
        sendData(zeros, 128);
    }
}

void OLEDDisplay::displayText(const char* text, uint8_t row) {
    const uint8_t setupCommands[] = {
        SSD1306_COLUMNADDR, 0, 127,
        SSD1306_PAGEADDR, row, row
    };
    
    for (uint8_t cmd : setupCommands) {
        sendCommand(cmd);
    }
    
    uint8_t buffer[128] = {0};
    size_t pos = 0;
    
    for (const char* p = text; *p && pos < 128; p++) {
        if (*p >= 32 && *p <= 127) {
            const uint8_t* charData = font5x8[*p - 32];
            for (uint8_t j = 0; j < 5 && pos < 128; j++) {
                buffer[pos++] = charData[j];
            }
            if (pos < 128) buffer[pos++] = 0x00;
        }
    }
    
    sendData(buffer, 128);
}

void OLEDDisplay::displayHeight(int height) {
    char buffer[32];
    snprintf(buffer, sizeof(buffer), "Height: %d cm", height);
    clear();
    displayText(buffer, 3);
}
