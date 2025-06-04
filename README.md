# 🧩 Drupal-Appwrite Integration Module

![GSoC 2025](https://img.shields.io/badge/GSoC-2025-blue?logo=google)
![Drupal](https://img.shields.io/badge/Built%20For-Drupal-blue?logo=drupal)
![Appwrite](https://img.shields.io/badge/Powered%20By-Appwrite-F02E65?logo=appwrite)

A Google Summer of Code 2025 project integrating modern Appwrite backend services with Drupal. Built to empower developers with seamless OAuth login, cloud-native file storage, and document sync capabilities — all configurable from within Drupal.

---

## 🚀 Project Overview

This module bridges the Drupal CMS with the Appwrite Backend-as-a-Service (BaaS) platform. 

It allows developers to:

- Integrate **OAuth2-based login** from providers like Google, GitHub, Apple
- Store and retrieve media files using **Appwrite's object storage**
- Sync Drupal content types with **Appwrite's document database**
- Manage settings via an **admin-friendly configuration UI**

---

## 💡 Why This Matters

Drupal has long been a CMS powerhouse, but it lacks built-in integrations for modern BaaS platforms. 

This project fills that gap by:

- Enabling secure social login via Appwrite OAuth
- Offloading media management to scalable Appwrite storage
- Syncing content with a modern NoSQL document DB
- Providing a developer-friendly UI for configuration
- Laying the foundation for future support (Functions, Realtime, Messaging)
- By bridging two open-source giants, this module unlocks new potential for Drupal developers and site builders alike.

---

## 🌐 Live Demo (Planned)

A hosted showcase site demonstrating login, storage, and content sync flows using Drupal + Appwrite.

---

## 🎯 Key Features

- 🔐 OAuth2 login via Appwrite (Google, GitHub, Apple, etc.)
- 👥 User session mapping between Appwrite and Drupal
- 🗂️ Media uploads handled by Appwrite's bucket-based storage
- 📄 Sync Drupal content nodes to Appwrite’s document DB
- ⚙️ Admin configuration panel for all Appwrite credentials and toggles
- 🧪 Built-in test mode and debug logging for integration issues
- 🔌 Modular, extensible architecture using Drupal services and DI

---

## 📦 Installation

### Requirements

- Drupal 10+
- PHP 8.1+
- Composer
- Appwrite (self-hosted or cloud)

### Steps

```bash
composer require drupal/appwrite_integration
drush en appwrite_integration
```

Then, configure the module at /admin/config/appwrite:
- Appwrite Project ID
- API Endpoint
- API Key
- OAuth Providers
- Bucket ID
- Document sync options

---

## 🧪 Usage Guide

- Visit /appwrite/login to authenticate via Google, GitHub, etc.
- On successful login, the user is redirected to a custom dashboard.
- Uploaded media files are stored in Appwrite buckets.
- Selected content types are synced with Appwrite documents.
- Visit /appwrite/logout to securely end sessions.

---

## 💬 Community & Contribution
This module is developed as part of GSoC 2025 under the Drupal organization and the mentorship of Appwrite contributors.

### How to Contribute
- ⭐ Star the repo
- 🐛 File issues for bugs or suggestions
- 🚀 Submit a pull request
- 📣 Share your use case with us

### Maintainer
- Ritesh Kumar Singh
  - https://riteshsingh.vercel.app
  - GitHub: @riteshdavv
  - Drupal.org: riteshdavv
  - Email: ritesh.davv@gmail.com

---

## Acknowledgements
- 🧠 Mentors: Abhinav Jha, Ujjval Kumar
- 🛠️ Organization: Appwrite
- 🌍 Host Community: Drupal
- ❤️ GSoC 2025 & Open Source community
