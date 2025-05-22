# Telegram Bot Integration

This document describes how to set up and use the Telegram Bot integration for sending notifications from the PistockLNT system.

## Overview

The Telegram Bot integration allows the system to send notifications to Telegram channels or groups for important events such as:

- New orders
- Low stock alerts
- Payment confirmations
- User registrations
- System errors
- Custom notifications

Only Super Admin users can manage the Telegram bot settings and notification templates.

## Setup Instructions

### 1. Create a Telegram Bot

1. Open Telegram and search for "BotFather"
2. Start a chat with BotFather and send the command `/newbot`
3. Follow the instructions to create a new bot
4. Once created, BotFather will provide a token for your bot. Save this token for the next step

### 2. Get Your Chat ID

1. Start a chat with your new bot or add it to a group where you want to receive notifications
2. Send a message to the bot or in the group (with the bot added)
3. Visit `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates` in a browser (replace `<YOUR_BOT_TOKEN>` with the token from step 1)
4. Look for the `"chat":{"id":123456789}` value in the response. This is your chat ID

### 3. Configure the Bot in PistockLNT

1. Login to PistockLNT as a Super Admin
2. Navigate to "Telegram Bot Settings" in the menu
3. Click "Add New Bot" and fill in:
   - Bot Name: A friendly name for your bot
   - Bot Token: The token from BotFather
   - Chat ID: The chat ID from step 2
   - Webhook URL: (Optional) A URL to receive bot updates if needed
4. Click "Test Connection" to verify the setup
5. Save the settings

### 4. Manage Notification Templates

The system includes several pre-configured notification templates. You can:

1. Edit existing templates
2. Create new templates
3. Deactivate templates you don't need

Template messages support variables in the format `{{variable_name}}`. These variables will be replaced with actual values when the notification is sent.

## Using the Notification System in Code

### Sending a Notification with Template

```php
// Include the necessary file
require_once 'php_action/telegram_notification.php';

// Send a notification using a template
$data = [
    'order_id' => '12345',
    'client_name' => 'Company ABC',
    'amount' => '$1,500.00',
    'order_date' => date('Y-m-d H:i')
];

sendTemplateNotification('new_order', $data);
```

### Sending a Direct Message

```php
// Include the necessary file
require_once 'php_action/telegram_notification.php';

// Send a direct message without using a template
$message = "This is a custom message without using a template";
sendTelegramNotification($message);
```

## Notification Logs

All notifications are logged in the system. You can view:

- The template used
- The actual message content
- The status (sent, failed, pending)
- Any error messages
- The date and time of sending

The logs can help troubleshoot any issues with notifications and provide an audit trail of sent notifications.

## Best Practices

1. Use templates instead of direct messages whenever possible
2. Keep template keys consistent with your notification types
3. Include all relevant information in notifications but keep them concise
4. Test your templates after creating or modifying them
5. Regularly check the notification logs for any failed notifications

## Troubleshooting

If notifications are not being sent:

1. Verify the bot token and chat ID are correct
2. Check that the bot is active in the system
3. Make sure the template being used is active
4. Check the notification logs for error messages
5. Verify that the bot has been added to the chat/group
6. Ensure the bot has the necessary permissions in the chat/group 