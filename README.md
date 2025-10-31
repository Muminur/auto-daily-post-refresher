# 📅 Auto Daily Post Refresher

> Automatically update post publication dates daily to keep your content fresh and improve SEO rankings. Set it and forget it automation.

[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Stable Version](https://img.shields.io/badge/Stable-1.0.8-brightgreen.svg)](https://github.com/Muminur/auto-daily-post-refresher)

---

## 📖 Description

**Auto Daily Post Refresher** is a powerful WordPress plugin that automatically updates post publication dates on a daily basis, helping your content appear fresh to search engines and visitors. Perfect for content-heavy sites that want to maintain the appearance of regularly updated content without manual intervention.

---

## ✨ Key Features

### 🤖 Automation & Scheduling
- **Automated Daily Updates** - Set it and forget it! The plugin automatically updates selected posts every day via WordPress cron
- **Flexible Scheduling** - Configure what time of day updates should occur (default: 3:00 AM)
- **Manual Trigger** - Test updates or run them on-demand with dry-run option for safety
- **Batch Processing** - Handles thousands of posts efficiently without timing out or causing memory issues

### 🎯 Smart Post Selection
- **Smart Post Selection** - Choose exactly which posts should be auto-updated using an intuitive filterable interface
- **Advanced Filtering** - Filter posts by type, category, author, and auto-update status
- **Bulk Actions** - Enable or disable auto-update for multiple posts at once
- **Custom Post Types** - Works with all post types including WooCommerce products

### 📊 Monitoring & Reporting
- **Comprehensive Logging** - Track every update with detailed activity logs showing before/after dates
- **Dashboard Widget** - Quick stats display right on your WordPress dashboard
- **Email Notifications** - Optional email alerts for successful updates or errors
- **CSV Export** - Export update logs to CSV for external analysis and record-keeping

### 🛠️ Advanced Features
- **Real-Time Updates** - AJAX-powered interface with instant toggle switches and progress indicators
- **Undo Functionality** - Quickly revert recent bulk changes within a 5-minute window
- **Keyboard Shortcuts** - Power user features with keyboard navigation (press ? for help)
- **Contextual Help** - Built-in help system with FAQ, troubleshooting, and use cases

### 🔒 Security & Performance
- **Safe & Secure** - Built following WordPress coding standards and security best practices
- **Zero Conflicts** - Works alongside other plugins with proper namespacing
- **Performance Optimized** - Minimal impact on site performance with intelligent caching and transient locking
- **Accessibility** - Full ARIA support and keyboard navigation for inclusive user experience

### 🌐 Compatibility
- **Multisite Compatible** - Works seamlessly in WordPress multisite environments
- **Fully Translatable** - Ready for internationalization with complete i18n support
- **Page Builder Ready** - Compatible with Elementor, Divi, Beaver Builder, and all major page builders

---

## 🎯 Why Use This Plugin?

| Benefit | Description |
|---------|-------------|
| 🚀 **SEO Benefits** | Search engines favor fresh content. Keep your posts appearing recent to improve search rankings. |
| ⏰ **Time Savings** | No more manual date updates. Automate your workflow and focus on content creation. |
| 📈 **Content Strategy** | Implement a systematic approach to content freshness across your entire site. |
| 👥 **User Engagement** | Visitors see updated dates, improving perceived content relevance and credibility. |
| ♻️ **Evergreen Content** | Keep timeless articles appearing current without constant manual updates. |
| 📢 **Content Marketing** | Maintain an active publishing schedule appearance even during slow periods. |

---

## 🚀 Quick Start

### Installation

1. Download or clone this repository
2. Upload to `/wp-content/plugins/auto-daily-post-refresher` directory
3. Activate the plugin through WordPress admin
4. Navigate to **Auto Post Refresher** in the admin menu

### Initial Setup

#### Step 1: Select Posts
- Go to **Auto Post Refresher > Post Selector**
- Use checkboxes to select posts you want to auto-update
- Apply **Enable Auto-Update** bulk action

#### Step 2: Configure Settings
- Go to **Auto Post Refresher > Settings**
- Toggle **Enable Automatic Updates** to ON
- Set your preferred **Update Time** (default: 3:00 AM)
- Save settings

#### Step 3: Verify & Monitor
- Check **System Status** shows "Cron Status: Scheduled"
- View **Dashboard Widget** for quick stats
- Monitor **Update Logs** page for detailed history

---

## 📋 Requirements

| Requirement | Minimum Version |
|-------------|----------------|
| 🌐 **WordPress** | 5.0+ (Tested up to 6.4) |
| 🐘 **PHP** | 7.4+ (8.0+ recommended) |
| 🗄️ **MySQL** | 5.7+ or MariaDB 10.2+ |
| 💾 **Memory** | 128MB PHP memory limit |
| ⏰ **Cron** | WordPress cron enabled |

---

## 🎨 Screenshots & Interface

### Post Selector Interface
Filterable table showing all posts with checkboxes and toggle switches. Select exactly which posts to auto-update with advanced filters.

### Settings Panel
Comprehensive configuration with master enable/disable switch, scheduler, post type selection, and performance settings.

### Update Logs
Detailed history of all automatic updates with before/after dates, export to CSV functionality.

### Dashboard Widget
Quick stats showing posts marked for update, total updates performed, and recent activity.

---

## 💡 Use Cases

- **📰 Content Publishers** - Keep blog archives appearing fresh without republishing
- **🗞️ News Sites** - Rotate older articles to the top without manual intervention
- **🛒 E-commerce** - Keep product posts appearing current for better visibility
- **🎓 Membership Sites** - Maintain active appearance of evergreen educational content
- **🏢 Corporate Blogs** - Automate content freshness for marketing and SEO strategies
- **📚 Content Aggregators** - Keep curated content appearing updated automatically

---

## 🛡️ Security & Privacy

### Security Features
✅ Nonce verification on all forms and AJAX requests
✅ Capability checks on all admin pages
✅ Input sanitization using WordPress functions
✅ Output escaping to prevent XSS attacks
✅ Prepared statements for database queries
✅ CSRF protection on all state-changing operations
✅ OWASP Top 10 compliant
✅ Zero critical vulnerabilities

### Privacy Compliance
This plugin does **NOT**:
- ❌ Collect any user data
- ❌ Store personal information
- ❌ Transmit data to external servers
- ❌ Use cookies or tracking
- ❌ Share information with third parties

✅ **100% GDPR Compliant** - All operations are performed locally on your WordPress installation.

---

## 🔧 Advanced Configuration

### Batch Processing
```php
// Adjust batch size for large sites
Default: 50 posts per run
Range: 1-1000 posts
Configure in: Settings > Performance
```

### Custom Cron Schedule
```php
// For low-traffic sites, use system cron
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron
```

### Developer Hooks

#### Action Hooks
- `adpr_before_update` - Before updating a post
- `adpr_after_update` - After updating a post
- `adpr_cron_start` - When cron job starts
- `adpr_cron_complete` - When cron job completes

#### Filter Hooks
- `adpr_update_date` - Customize the update date/time
- `adpr_get_posts_for_update` - Customize post selection query
- `adpr_batch_size` - Modify batch size dynamically
- `adpr_settings_defaults` - Change default settings

---

## 🐛 Troubleshooting

<details>
<summary><strong>Updates aren't happening automatically</strong></summary>

Check these common issues:
1. Ensure "Enable Automatic Updates" is ON in Settings
2. Verify posts are marked in Post Selector (green checkmark)
3. Check Settings page shows "Cron Status: Scheduled"
4. Ensure DISABLE_WP_CRON is not set to true
5. Verify your server timezone is correct
</details>

<details>
<summary><strong>Manual trigger works but automatic doesn't</strong></summary>

This indicates a WordPress cron issue:
- WordPress cron requires site visits to trigger
- Install WP Crontrol plugin to debug cron
- Consider setting up system cron as alternative
- Check if a caching plugin is preventing wp-cron.php execution
</details>

<details>
<summary><strong>Memory exhausted errors</strong></summary>

Solutions:
1. Reduce Batch Size from 50 to 25 or 10 in Settings
2. Increase PHP Memory: Add to wp-config.php: `define('WP_MEMORY_LIMIT', '256M');`
3. Contact your hosting provider about memory limits
</details>

---

## 📚 Documentation

- **📖 Configuration Guide** - See `CONFIGURATION.md` for detailed setup instructions
- **🔌 Developer API** - See `HOOKS.md` for hooks and filters documentation
- **🧪 Testing Guide** - See `TESTING.md` for quality assurance procedures
- **🔐 Security Audit** - See `SECURITY.md` for security assessment details

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

- 🐛 Report bugs and issues
- 💡 Suggest new features and improvements
- 🔧 Submit pull requests
- 🌍 Help with translations
- 📝 Improve documentation

---

## 📝 Changelog

### Version 1.0.8 - 2025-10-31

**Features:**
- ✨ Automated daily post date updates via WordPress cron
- 🎯 Post selection interface with advanced filtering
- ⚙️ Comprehensive settings page with configurable options
- 📊 Activity logging system with before/after dates
- 🔄 Batch processing for large sites (1-1000 posts)
- 🎮 Manual update trigger with dry-run mode
- 📧 Email notification system
- 📤 CSV export for update logs
- ↩️ Undo functionality for bulk actions
- ⌨️ Keyboard shortcuts for power users
- ❓ Contextual help system with FAQ
- ♿ Full accessibility support
- 🌐 Multisite compatible
- 🌍 Internationalization ready

**Technical:**
- ⚡ Transient locking to prevent concurrent runs
- 🎯 Memory-efficient batch processing
- 🔍 Optimized database queries
- ✅ WordPress coding standards compliant
- 🔒 Security audited
- ✅ PHPUnit test suite included

---

## 👨‍💻 Author

**Md Muminur Rahman**

- 🌐 Website: [https://alternativechoice.org](https://alternativechoice.org)
- 💼 GitHub: [@Muminur](https://github.com/Muminur)

---

## 📄 License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 🙏 Acknowledgments

Special thanks to:
- WordPress community for the excellent documentation
- Katsushi Kawamori for the original Bulk Datetime Change plugin
- All beta testers and early adopters
- Contributors to the project

---

## ⭐ Support This Project

If you find this plugin useful, please:
- ⭐ Star this repository
- 🐛 Report bugs and issues
- 💡 Share your ideas and suggestions
- 📢 Spread the word

---

<p align="center">
  <strong>Developed with ❤️ for the WordPress community</strong>
</p>

<p align="center">
  <sub>Keep your content fresh automatically!</sub>
</p>
