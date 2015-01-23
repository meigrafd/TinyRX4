// RFM12B Receiver for RaspberryPI
//
// Basiert zum Teil auf der Arbeit von Nathan Chantrell
//
// modified by meigrafd @ 16.12.2013 - for UART on RaspberryPI
//------------------------------------------------------------------------------
#include <RFM12B.h>
#include <avr/sleep.h>
#include <SoftwareSerial.h>
//------------------------------------------------------------------------------
// You will need to initialize the radio by telling it what ID it has and what network it's on
// The NodeID takes values from 1-127, 0 is reserved for sending broadcast messages (send to all nodes)
// The Network ID takes values from 0-255
// By default the SPI-SS line used is D10 on Atmega328. You can change it by calling .SetCS(pin) where pin can be {8,9,10}
#define NODEID            22  //network ID used for this unit
#define NETWORKID        210  //the network ID we are on
#define ACK_TIME        2000  // # of ms to wait for an ack
#define SERIAL_BAUD     9600
//------------------------------------------------------------------------------
// PIN-Konfiguration 
//------------------------------------------------------------------------------
// UART pins
#define rxPin 7 // D7, PA3
#define txPin 3 // D3, PA7. pin der an RXD vom PI geht.
// LED pin
#define LEDpin 8 // D8, PA2 - set to 0 to disable LED
/*
                     +-\/-+
               VCC  1|    |14  GND
          (D0) PB0  2|    |13  AREF (D10)
          (D1) PB1  3|    |12  PA1 (D9)
             RESET  4|    |11  PA2 (D8)
INT0  PWM (D2) PB2  5|    |10  PA3 (D7)
      PWM (D3) PA7  6|    |9   PA4 (D6)
      PWM (D4) PA6  7|    |8   PA5 (D5) PWM
                     +----+
*/

//encryption is OPTIONAL
//to enable encryption you will need to:
// - provide a 16-byte encryption KEY (same on all nodes that talk encrypted)
// - to call .Encrypt(KEY) to start en
// - to stop encrypting call .Encrypt(NULL)
//#define KEY   "ABCDABCDABCDABCD"

// Initialise UART
SoftwareSerial mySerial(rxPin, txPin);

// Need an instance of the Radio Module
RFM12B radio;

//##############################################################################

static void activityLED (byte mode) {
  pinMode(LEDpin, OUTPUT);
  digitalWrite(LEDpin, mode);
}

// blink led
static void blink (byte pin, byte n = 3) {
  pinMode(pin, OUTPUT);
  for (byte i = 0; i < 2 * n; ++i) {
    delay(100);
    digitalWrite(pin, !digitalRead(pin));
  }
}

// init Setup
void setup() {
  pinMode(rxPin, INPUT);
  pinMode(txPin, OUTPUT);
  mySerial.begin(SERIAL_BAUD);
  radio.Initialize(NODEID, RF12_433MHZ, NETWORKID);
  #ifdef KEY
    radio.Encrypt((byte*)KEY);      //comment this out to disable encryption
  #endif
  if (LEDpin) {
    activityLED(1); // LED on
    delay(1000);
    activityLED(0); // LED off
  }
}

// Loop
void loop() {
  if (radio.ReceiveComplete()) {
    if (radio.CRCPass()) {
      //node ID of TX, extracted from RF datapacket. Not transmitted as part of structure
      mySerial.print(radio.GetSender(), DEC);
      mySerial.print(" ");
      int i;
      for (byte i = 0; i < *radio.DataLen; i++) //can also use radio.GetDataLen() if you don't like pointers
        mySerial.print((char) radio.Data[i]);

      if (LEDpin) {
        blink(LEDpin, 2);
      }
      if (radio.ACKRequested()) {
        radio.SendACK();
        mySerial.print(" - ACK sent");
      }
    } else {
      mySerial.print("BAD-CRC");
      if (LEDpin) {
        activityLED(1); // LED on
        delay(1000);
        activityLED(0); // LED off
      }
    }
    mySerial.println();
  }
}

