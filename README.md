# Appwrite-Drupal Integration Module

![GSoC 2025](https://img.shields.io/badge/GSoC-2025-blue?logo=google)
![Drupal](https://img.shields.io/badge/Built%20For-Drupal-blue?logo=drupal)
![Appwrite](https://img.shields.io/badge/Powered%20By-Appwrite-F02E65?logo=appwrite)

A Google Summer of Code 2025 project integrating modern Appwrite backend services with Drupal. Built to empower developers with seamless OAuth login, cloud-native file storage, and document sync capabilities; all configurable from within Drupal.

---

## Project Overview

This module bridges the Drupal CMS with the Appwrite Backend-as-a-Service (BaaS) platform. 

It allows developers to:

- Integrate **OAuth2-based login** from providers like Google, GitHub, Apple
- Store and retrieve media files using **Appwrite's object storage**
- Sync Drupal content types with **Appwrite's document database**
- Manage settings via an **admin-friendly configuration UI**

---

## Why This Matters

Drupal has long been a CMS powerhouse, but it lacks built-in integrations for modern BaaS platforms. 

This project fills that gap by:

- Enabling secure social login via Appwrite OAuth
- Offloading media management to scalable Appwrite storage
- Syncing content with a modern NoSQL document DB
- Providing a developer-friendly UI for configuration
- Laying the foundation for future support (Functions, Realtime, Messaging)
- By bridging two open-source giants, this module unlocks new potential for Drupal developers and site builders alike.

---

## Features

- **OAuth Authentication** via Appwrite (e.g., GitHub, Google login flows).
- **Bi-directional User Sync** between Drupal and Appwrite (session + logout).
- **Storage API Integration**: upload, list, and manage files in Appwrite buckets directly from Drupal.
- **Document Database Integration**:
  - One-way sync (Drupal → Appwrite) for configured content types.
  - Retrieval (Appwrite → Drupal) for documents/blocks.
  - Mapping system between Node IDs and Appwrite Document IDs.
  - Sync log with status and error messages.
- **Permissions Integration**:
  - `sync content to appwrite`
  - `view appwrite documents`
  - `administer appwrite sync logs`
- **Responsive Configuration UI** for admins (mobile/tablet friendly).
- Fully tested across **Lando, DDEV, and Docker** environments.

---

## Requirements

- Drupal 10+
- PHP 8.1+
- Composer
- Appwrite (self-hosted or cloud)

---

## Installation

### Steps:

1. **Clone the repository** into your Drupal `modules/custom` directory:

  ```bash
   cd web/modules/custom
   git clone https://github.com/riteshdavv/drupal-appwrite.git appwrite_integration
   ```

2. **Install dependencies:**

  ``` bash
  composer require appwrite/appwrite
  ```

3. **Enable the module:**
  ```bash
  drush en appwrite_integration
  drush cr
  ```

### Configuration:

1. Go to: &nbsp; **Admin → Configuration → Web Services → Appwrite Integration**

2. Enter your Appwrite details:

- Appwrite Endpoint (e.g., **https://fra.cloud.appwrite.io/v1**)
- Project ID
- API Key (with necessary scopes)
- Bucket ID

3. Select which content types should sync to Appwrite.

4. Assign permissions via: &nbsp; **People → Roles → Permissions**

---

## Walkthrough Demos

- This Google Drive folder contains all walkthrough demonstration videos related to the authentication, storage, and document database integration features of the Appwrite-Drupal integration module.
- [View here](https://drive.google.com/drive/folders/11vahfSDrYrMhY2ISYNtwfB8Dn5vSSBdT)

---

## Final Term Submission - GitHub Gist

- This GitHub Gist serves as a consolidated record of all weekly reports, documented learning outcomes, community and ecosystem impact, as well as the proposed future work related to this project.
- [View here](https://gist.github.com/riteshdavv/a4530c8e44162db6a3e3ac64ab8c3b25)

---

## Usage Guide

### Content Sync

- When a node of a sync-enabled content type is created/updated/deleted → it will sync automatically with Appwrite Document DB.
- Sync logs are available at: &nbsp; **Admin → Content → Appwrite Sync Logs**

### Document Retrieval

- Appwrite documents can be retrieved via route: 
  ```bash
  /appwrite/document/{databaseId}/{collectionId}/{documentId}
  ```

### Storage Integration

- Create and manage buckets.
- Upload, list, and download files from Appwrite within Drupal.

---

## Testing

The module includes PHPUnit unit tests with Appwrite SDK mocks/stubs.

**Run tests:**
```bash
./vendor/bin/phpunit --group appwrite_integration
```

The module has been validated in:
- Lando
- DDEV
- Docker Compose

---

## Community & Contribution
This module is developed as part of GSoC 2025 under the Drupal organization and the mentorship of Appwrite contributors.

### How to Contribute
- Fork the repository
- Create a feature branch
- Submit a pull request
- Share your use case with us

### Maintainer
- Ritesh Kumar Singh
  - GitHub: https://github.com/riteshdavv
  - LinkedIn: https://linkedin.com/in/riteshdavv
  - Portfolio: https://riteshsingh.vercel.app
  - Drupal.org: riteshdavv
  - Email: ritesh.davv@gmail.com

---

## Acknowledgements

Special thanks to the Drupal community and GSoC mentors for guidance.
- Mentors: Abhinav Jha, Ujjval Kumar
- Organization: Appwrite
- Host Community: Drupal
- GSoC 2025 & Open Source community


