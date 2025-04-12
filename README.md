# IPN Foundation Event Management - User Permissions System

This document provides instructions for setting up and using the user permissions system for the IPN Foundation Event Management application.

## Overview

The user permissions system allows administrators to control which users have access to specific event data. It enables:

- Granular control over which users can view specific event types
- Permissions for data export functions
- Audit logging of all user actions
- A central management interface for user permissions

## Installation

### Step 1: Run the Setup Script

1. Log in to the application as an administrator (user accounts: `imkaadarsh` or `gaurava.ipn`)
2. Navigate to "Setup Permissions" in the sidebar under the Administration section
3. Review the setup process and click "Run Setup"
4. The system will create the necessary database tables and import existing users

### Step 2: Verify User Permissions

After installation, verify that:

- Admin users have full access to all events
- Regular users only have access to events explicitly granted to them

## Managing User Permissions

### Assigning Permissions

1. Navigate to "User Permissions" in the admin sidebar
2. Select a user from the list on the left
3. Check the appropriate boxes for each event type:
   - **View**: Allows the user to view event data
   - **Export**: Allows the user to export event data to CSV, PDF, and Excel
4. Click "Save Permissions" to apply the changes

### Testing Permissions

1. Log out and log back in as a different user
2. Verify that only the granted event types appear in the sidebar
3. For events with export permission, verify that export buttons appear in the data tables

## Implementation Details

### Database Schema

The system uses two main tables:

**ipn_events_dash_users**
- `id`: Primary key
- `username`: Unique username
- `password`: Hashed password (placeholder for .env-based auth)
- `role`: Either 'admin' or 'user'
- `created_at` and `updated_at`: Timestamps

**user_permissions**
- `id`: Primary key
- `user_id`: Username
- `event_type`: Type of event (conclaves, yuva, etc.)
- `can_view`: Boolean flag for view access
- `can_export`: Boolean flag for export access
- `created_at` and `updated_at`: Timestamps

### Key Functions

The system provides several utility functions:

- `isAdmin()`: Checks if the current user has administrator privileges
- `canViewEvent($event_type)`: Checks if the current user can view a specific event type
- `canExportEvent($event_type)`: Checks if the current user can export data for a specific event type
- `getUserViewableEvents()`: Returns an array of all event types the current user can view

## Troubleshooting

### Common Issues

1. **Permission changes not taking effect**: Clear browser cache or log out and log back in
2. **Setup script errors**: Check database credentials and permissions in your `.env` file
3. **Missing sidebar items**: Verify that the user has been granted permissions for those events
4. **Export buttons not showing**: Confirm that the user has export permissions for that event type

### Support

For additional support, contact:
- Technical Support: `imkaadarsh` (Admin)
- User Management: `gaurava.ipn` (Admin) 