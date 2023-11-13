# BuyByRaffle Plugin API Documentation

This document provides comprehensive instructions on how to use the REST API routes added by the BuyByRaffle WordPress plugin. The focus is on initiating raffles and gifting cashtokens.

## Overview

The BuyByRaffle plugin introduces two key actions via the WordPress REST API:

1. **Initiate Raffle:** Trigger a new raffle draw.
2. **Gift Cashtoken:** Award cashtokens to customers based on their orders.

## API Endpoints

### 1. Initiate Raffle

This endpoint triggers the start of a new raffle draw.

- **Endpoint:** `/wp-json/buybyraffle/v1/queue`
- **HTTP Method:** `POST`
- **Action:** `initiate_raffle`

#### Payload
- `raffle_cycle_id`: (integer) The ID of the raffle draw to be initiated.

#### Example Request
```bash
curl -X POST 'http://yourwebsite.com/wp-json/buybyraffle/v1/queue' \
-H 'Content-Type: application/json' \
-d '{
      "action": "initiate_raffle",
      "raffle_cycle_id": 123
    }'
```
### 2. Gift Cashtoken

This endpoint handles the gifting of cashtokens based on customer orders.
- **Endpoint:** /wp-json/buybyraffle/v1/queue
- **HTTP Method:** POST
- **Action:** giftcashtoken

### Payload

- `order_id`: (integer) The ID of the order for which cashtokens are to be gifted.

### Example Request
```bash
curl -X POST 'http://yourwebsite.com/wp-json/buybyraffle/v1/queue' \
-H 'Content-Type: application/json' \
-d '{
      "action": "giftcashtoken",
      "order_id": 456
    }'
```
### Important Notes

  * Ensure the BuyByRaffle plugin is active on your WordPress site.
  * Replace 'http://yourwebsite.com' with the actual URL where your WordPress site is hosted.
  * The raffle_cycle_id and order_id must be valid and exist in your system.
  * Responses will include HTTP status codes to indicate the outcome of the request. It is crucial for client applications to handle these appropriately.
  * Depending on your WordPress configuration, authentication may be required to access these endpoints.
  * The giftcashtoken route derives customer details from the provided order_id.