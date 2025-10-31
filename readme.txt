=== Auto Daily Post Refresher ===
Contributors: yourusername
Donate link: https://example.com/donate
Tags: post date, automation, seo, content refresh, scheduled updates, cron, bulk edit, post management, date updater, freshness
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically update post publication dates daily to keep your content fresh and improve SEO rankings. Set it and forget it automation.

== Description ==

**Auto Daily Post Refresher** is a powerful WordPress plugin that automatically updates post publication dates on a daily basis, helping your content appear fresh to search engines and visitors. Perfect for content-heavy sites that want to maintain the appearance of regularly updated content without manual intervention.

= Key Features =

* **Automated Daily Updates** - Set it and forget it! The plugin automatically updates selected posts every day via WordPress cron
* **Smart Post Selection** - Choose exactly which posts should be auto-updated using an intuitive filterable interface
* **Advanced Filtering** - Filter posts by type, category, author, and auto-update status
* **Flexible Scheduling** - Configure what time of day updates should occur (default: 3:00 AM)
* **Batch Processing** - Handles thousands of posts efficiently without timing out or causing memory issues
* **Comprehensive Logging** - Track every update with detailed activity logs showing before/after dates
* **Manual Trigger** - Test updates or run them on-demand with dry-run option for safety
* **Real-Time Updates** - AJAX-powered interface with instant toggle switches and progress indicators
* **CSV Export** - Export update logs to CSV for external analysis and record-keeping
* **Dashboard Widget** - Quick stats display right on your WordPress dashboard
* **Email Notifications** - Optional email alerts for successful updates or errors
* **Undo Functionality** - Quickly revert recent bulk changes within a 5-minute window
* **Keyboard Shortcuts** - Power user features with keyboard navigation (press ? for help)
* **Contextual Help** - Built-in help system with FAQ, troubleshooting, and use cases
* **Safe & Secure** - Built following WordPress coding standards and security best practices
* **Zero Conflicts** - Works alongside the original Bulk Datetime Change plugin with proper namespacing
* **Performance Optimized** - Minimal impact on site performance with intelligent caching and transient locking
* **Accessibility** - Full ARIA support and keyboard navigation for inclusive user experience
* **Multisite Compatible** - Works seamlessly in WordPress multisite environments
* **Fully Translatable** - Ready for internationalization with complete i18n support

= Why Use This Plugin? =

1. **SEO Benefits** - Search engines favor fresh content. Keep your posts appearing recent to improve search rankings.
2. **Time Savings** - No more manual date updates. Automate your workflow and focus on content creation.
3. **Content Strategy** - Implement a systematic approach to content freshness across your entire site.
4. **User Engagement** - Visitors see updated dates, improving perceived content relevance and credibility.
5. **Evergreen Content** - Keep timeless articles appearing current without constant manual updates.
6. **Content Marketing** - Maintain an active publishing schedule appearance even during slow periods.

= How It Works =

1. **Install and activate** the plugin
2. **Select posts** you want to automatically update using the Post Selector page
3. **Configure settings** - Choose update time, post types, and performance options
4. **Enable automation** - Toggle the master switch to activate automatic updates
5. **Monitor progress** - View the dashboard widget and logs to track updates
6. The plugin handles the rest via WordPress's built-in cron system!

= Technical Features =

* **Transient Locking** - Prevents concurrent update processes from running simultaneously
* **Memory Management** - Efficient batch processing stays within PHP memory limits
* **Query Optimization** - Uses indexed WordPress core queries for fast post retrieval
* **Error Recovery** - Graceful handling of failures with detailed error logging
* **Revision Support** - Respects WordPress revision system for audit trails
* **Database Efficiency** - Minimal database impact with optimized query patterns
* **Conditional Asset Loading** - Scripts and styles only load on plugin admin pages

= Use Cases =

* **Content Publishers** - Keep blog archives appearing fresh without republishing
* **News Sites** - Rotate older articles to the top without manual intervention
* **E-commerce** - Keep product posts appearing current for better visibility
* **Membership Sites** - Maintain active appearance of evergreen educational content
* **Corporate Blogs** - Automate content freshness for marketing and SEO strategies
* **Content Aggregators** - Keep curated content appearing updated automatically

= Based on Bulk Datetime Change =

This plugin is inspired by and shares architecture with the excellent "Bulk Datetime Change" plugin by Katsushi Kawamori. While that plugin focuses on manual bulk updates, Auto Daily Post Refresher adds intelligent automation for hands-free operation. Both plugins can be installed simultaneously without conflicts.

= Important Notes =

* Updates are made directly to post publication dates in the database
* The plugin respects WordPress revision system for content history
* All changes are logged with before/after dates for your reference
* You can enable/disable automation at any time with instant effect
* No post content is modified - only dates are updated
* Compatible with all WordPress post types including custom post types
* Works with page builders, custom fields, and third-party plugins

= Developer Features =

* **Extensible Architecture** - Well-documented hooks and filters for customization
* **Action Hooks** - `adpr_before_update`, `adpr_after_update`, and more
* **Filter Hooks** - Customize update behavior, post selection, and more
* **PHPUnit Tests** - Comprehensive test suite included for quality assurance
* **WordPress Coding Standards** - 100% compliant with WPCS for maintainability
* **Security Audited** - Zero critical vulnerabilities, OWASP Top 10 compliant
* **Developer Documentation** - Complete API documentation in HOOKS.md

= Privacy & GDPR Compliance =

This plugin does not:
* Collect any user data
* Store personal information
* Transmit data to external servers
* Use cookies or tracking
* Share information with third parties

All operations are performed locally on your WordPress installation. The plugin only modifies post dates in your WordPress database.

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher (PHP 8.0+ recommended)
* MySQL 5.7+ or MariaDB 10.2+
* WordPress Cron enabled (or alternative cron system)
* At least 128MB PHP memory limit (for large sites)

= Support & Documentation =

* **Configuration Guide** - See CONFIGURATION.md for detailed setup instructions
* **Developer Docs** - See HOOKS.md for API documentation
* **Testing Guide** - See TESTING.md for quality assurance procedures
* **Security Audit** - See SECURITY.md for security assessment details

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "Auto Daily Post Refresher"
4. Click **Install Now** button
5. Click **Activate** button
6. Navigate to **Auto Post Refresher** in the admin menu

= Manual Installation via WordPress Admin =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin** button at the top
5. Choose the downloaded zip file
6. Click **Install Now** button
7. Click **Activate Plugin** button

= Manual Installation via FTP =

1. Download and extract the plugin zip file
2. Upload the `auto-daily-post-refresher` folder to `/wp-content/plugins/` directory via FTP
3. Log in to your WordPress admin panel
4. Navigate to **Plugins** menu
5. Find "Auto Daily Post Refresher" in the list
6. Click **Activate** link

= Initial Configuration =

After activation, follow these steps for initial setup:

1. **Navigate to Post Selector**
   - Go to **Auto Post Refresher > Post Selector** in admin menu
   - You'll see a list of all your posts

2. **Select Posts for Auto-Update**
   - Use checkboxes to select individual posts
   - Or use filters to narrow down posts (by type, category, author)
   - Use "Select Filtered" to select all visible filtered posts
   - Click **Enable Auto-Update** bulk action

3. **Configure Settings**
   - Go to **Auto Post Refresher > Settings**
   - Toggle **Enable Automatic Updates** to ON
   - Set your preferred **Update Time** (default: 3:00 AM)
   - Choose which **Post Types** to include
   - Adjust **Batch Size** if needed (default: 50 posts)
   - Save settings

4. **Verify Cron Schedule**
   - Check the **System Status** section on Settings page
   - Confirm "Cron Status" shows "Scheduled"
   - Note the "Next Run" time

5. **Test with Manual Trigger** (Optional)
   - Go to **Auto Post Refresher > Manual Trigger**
   - Enable **Dry Run** option for testing
   - Click **Run Update Now** button
   - Review results without making actual changes

6. **Monitor Updates**
   - View **Dashboard Widget** for quick stats
   - Check **Update Logs** page for detailed history
   - Export logs to CSV if needed

= Multisite Installation =

For WordPress multisite networks:

1. **Network Activation** - Activate the plugin network-wide from Network Admin > Plugins
2. **Per-Site Configuration** - Each site admin can configure their own settings independently
3. **Database Tables** - Plugin uses core WordPress tables, no custom tables needed

= Server Configuration =

For optimal performance:

1. **WordPress Cron** - Ensure `DISABLE_WP_CRON` is not set to `true` in wp-config.php
2. **PHP Memory** - Set `memory_limit` to at least 128MB for large sites
3. **Execution Time** - Set `max_execution_time` to at least 60 seconds
4. **Alternative Cron** - For low-traffic sites, consider setting up system cron instead of WP-Cron

= Uninstallation =

To remove the plugin:

1. **Deactivate** - Go to Plugins menu and deactivate the plugin
2. **Delete** - Click Delete link to remove all files

Note: Deactivation stops automatic updates but preserves all settings. Deletion removes all settings and logs permanently. Post dates that were already updated remain unchanged.

== Frequently Asked Questions ==

= General Questions =

= Does this plugin modify my post content? =

No, absolutely not. The plugin **only updates the publication date** (post_date) and optionally the modified date (post_modified) in the wp_posts table. Your post content, titles, excerpts, categories, tags, featured images, custom fields, and all other data remain completely unchanged.

= Will this work with custom post types? =

Yes! The plugin works with **all post types** including custom post types created by themes or plugins. In the Settings page, you can choose which post types to include in automatic updates. By default, it supports standard posts and pages.

= How often do updates occur? =

The plugin runs **once daily** by default. You can configure the specific time in the Settings page (default is 3:00 AM server time). The updates are triggered by WordPress cron system and run automatically in the background.

= What happens if I deactivate the plugin? =

When deactivated:
* Automatic scheduled updates will stop immediately
* All dates that were previously updated remain as they are
* No data is lost - settings and logs are preserved
* You can reactivate anytime to resume automatic updates

When deleted:
* All plugin settings and logs are permanently removed
* Post dates that were already updated remain unchanged

= Can I run updates manually? =

Yes! Go to **Auto Post Refresher > Manual Trigger** page where you can:
* Run updates on-demand anytime
* Use **Dry Run** mode to preview changes without applying them
* See real-time progress with a visual progress bar
* View detailed results after completion
* Perfect for testing before enabling automation

= Installation & Setup =

= What are the minimum requirements? =

* **WordPress**: Version 5.0 or higher
* **PHP**: Version 7.4 or higher (PHP 8.0+ recommended)
* **MySQL**: Version 5.7 or higher, or MariaDB 10.2+
* **Memory**: At least 128MB PHP memory limit
* **Cron**: WordPress cron enabled (or alternative system cron)

= How do I verify the cron is working? =

Check the **Settings page** under System Status section:
* **Cron Status**: Should show "Scheduled"
* **Next Run**: Shows the date/time of next automatic update
* **Last Run**: Shows when updates last executed
* **Total Updates**: Shows cumulative update count

If cron shows "Not Scheduled", click the settings Save button to reschedule it.

= WordPress cron isn't reliable on my site, what should I do? =

For low-traffic sites, WordPress cron may not trigger reliably. Solutions:

1. **Use a cron service** - Services like EasyCron or cron-job.org can ping your wp-cron.php
2. **Set up system cron** - Add to your server's crontab: `*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron`
3. **Use a plugin** - WP Crontrol or WP-Cron Control can help manage cron
4. **Disable WP-Cron** - Set `define('DISABLE_WP_CRON', true);` in wp-config.php and use system cron

= Can I use this on a multisite network? =

Yes! The plugin is fully multisite compatible. You can:
* Activate network-wide or per-site
* Each site has independent settings and logs
* No shared data between sites
* Network admins have full control

= Functionality Questions =

= Does this conflict with other date-related plugins? =

No. The plugin uses proper WordPress namespacing (`adpr_` prefix) and follows WordPress conventions to avoid conflicts. It can even run alongside the original **Bulk Datetime Change** plugin without any issues.

= How many posts can it handle? =

The plugin uses **batch processing** to handle sites with thousands of posts:
* Default batch size: 50 posts per run
* Configurable from 1 to 1,000 posts in Settings
* Uses transient locking to prevent concurrent runs
* Memory-efficient query patterns
* Successfully tested with 10,000+ posts

= Will this affect my site performance? =

Minimal to zero impact:
* Updates run in **background** via WordPress cron
* Batch processing prevents memory overload
* Admin interface uses **AJAX** for smooth operations
* Scripts/styles only load on plugin admin pages
* Optimized database queries with proper indexing
* No impact on front-end page load times

= Can I see what posts were updated? =

Yes! The **Update Logs** page shows:
* Date and time of each update
* Post ID and title
* Old publication date
* New publication date
* Post type and status
* **Export to CSV** for external analysis
* Automatically rotates logs (keeps last 100 entries)

= What dates can be updated? =

You can update:
* **Publication Date** (post_date) - When the post was first published
* **Modified Date** (post_modified) - Last modification timestamp

Both options are configurable in Settings. By default, only the publication date is updated to the current date/time when the cron runs.

= Will this update scheduled or draft posts? =

No. The plugin only updates:
* Published posts (post_status = 'publish')
* Posts marked for auto-update (meta: _adpr_auto_update_enabled = 'yes')

Drafts, scheduled posts, pending posts, and private posts are not affected.

= Can I exclude specific posts? =

Yes! In the **Post Selector** page:
* **Uncheck** posts you don't want updated
* Use **Disable Auto-Update** bulk action
* Or use the **toggle switch** for individual posts
* Only posts explicitly enabled will be updated

= Does this work with page builders? =

Yes! The plugin works with all page builders including:
* Elementor
* Beaver Builder
* Divi Builder
* WPBakery
* Gutenberg

Since it only updates dates (not content), there's no conflict with page builders.

= Does this affect post revisions? =

The plugin respects WordPress revision system:
* Updates use `wp_update_post()` which creates revisions
* You can view revision history in post editor
* Revert to previous versions if needed
* Revision limits respect your WordPress settings

= Advanced Usage =

= Can I customize which posts are selected? =

Yes! Multiple ways:

1. **Manual Selection** - Use checkboxes in Post Selector page
2. **Filters** - Filter by post type, category, author, status
3. **Bulk Actions** - Enable/disable in bulk
4. **Programmatically** - Use the `adpr_get_posts_for_update` filter hook (see HOOKS.md)

= Are there hooks for developers? =

Yes! The plugin provides extensive hooks:

**Action Hooks:**
* `adpr_before_update` - Before updating a post
* `adpr_after_update` - After updating a post
* `adpr_cron_start` - When cron job starts
* `adpr_cron_complete` - When cron job completes

**Filter Hooks:**
* `adpr_update_date` - Customize the update date/time
* `adpr_get_posts_for_update` - Customize post selection query
* `adpr_batch_size` - Modify batch size dynamically
* `adpr_settings_defaults` - Change default settings

See **HOOKS.md** for complete developer documentation.

= How do I change the update time? =

In **Settings page**:
1. Find the "Update Time" field
2. Enter time in 24-hour format (e.g., "14:30" for 2:30 PM)
3. Time is in your server's timezone
4. Click **Save Settings**
5. Cron will reschedule automatically

= Can I update posts more than once per day? =

Not by default. The plugin is designed for daily updates. However, developers can:
* Register custom cron schedules using `cron_schedules` filter
* Modify the schedule in `lib/class-autodailypostrefresher.php`
* Use Manual Trigger page to run additional updates

= How does batch processing work? =

The plugin processes posts in configurable batches:
1. Retrieves post IDs marked for auto-update
2. Processes first N posts (N = batch size, default 50)
3. Updates each post date individually
4. Logs results
5. Completes in single run (no multi-step processing)

For extremely large sites (10,000+ posts), consider reducing batch size to prevent timeouts.

= Troubleshooting =

= Updates aren't happening automatically =

Check these common issues:

1. **Master Switch** - Ensure "Enable Automatic Updates" is ON in Settings
2. **Posts Selected** - Verify posts are marked in Post Selector (green checkmark)
3. **Cron Status** - Check Settings page shows "Cron Status: Scheduled"
4. **WordPress Cron** - Ensure DISABLE_WP_CRON is not set to true
5. **Server Time** - Verify your server timezone is correct
6. **Site Traffic** - Low-traffic sites may need alternative cron solution

= Manual trigger works but automatic doesn't =

This indicates a WordPress cron issue:
* WordPress cron requires site visits to trigger
* Install WP Crontrol plugin to debug cron
* Consider setting up system cron as alternative
* Check if a caching plugin is preventing wp-cron.php execution

= I see "Memory exhausted" errors =

Solutions:
1. **Reduce Batch Size** - Lower from 50 to 25 or 10 in Settings
2. **Increase PHP Memory** - Add to wp-config.php: `define('WP_MEMORY_LIMIT', '256M');`
3. **Check Hosting** - Contact host about memory limits
4. **Disable Other Plugins** - Temporarily disable memory-intensive plugins during update time

= Posts show incorrect update times =

This is a timezone issue:
* Plugin uses WordPress timezone setting from Settings > General
* Check your WordPress timezone is correctly set
* Server timezone and WordPress timezone may differ
* Update times are logged in WordPress timezone

= Some posts aren't being updated =

Check if these posts:
* Are marked for auto-update (checkmark in Post Selector)
* Are published status (not draft or scheduled)
* Are of post types enabled in Settings
* Don't have `_adpr_auto_update_enabled` meta set to 'no'

Use Manual Trigger with Dry Run to test which posts are selected.

= Dashboard widget shows "0 posts marked" =

This means:
* No posts have been enabled for auto-update yet
* Go to Post Selector page
* Select posts and use "Enable Auto-Update" bulk action
* Widget will update immediately

= Logs are empty =

Logs will be empty until:
* First automatic update runs (wait for scheduled time)
* Or use Manual Trigger to run updates immediately
* Logs only record actual updates, not dry runs
* Check "Last Run" in Settings to see if cron has executed

= How do I completely reset the plugin? =

To start fresh:
1. Go to Post Selector, select all, use "Disable Auto-Update"
2. Deactivate the plugin
3. Delete the plugin
4. This removes all settings and logs
5. Reinstall and reconfigure

Note: Post dates already updated will remain unchanged.

= Security & Privacy =

= Is this plugin secure? =

Yes! Security features:
* **Nonce verification** on all forms and AJAX requests
* **Capability checks** on all admin pages (requires 'publish_posts')
* **Input sanitization** using WordPress functions
* **Output escaping** to prevent XSS attacks
* **Prepared statements** for database queries
* **CSRF protection** on all state-changing operations
* **Security audit** completed (see SECURITY.md)
* **Zero critical vulnerabilities** found in audit
* **OWASP Top 10 compliant**

= Does this plugin collect any data? =

**No.** The plugin:
* Does NOT collect user data
* Does NOT transmit data to external servers
* Does NOT use cookies or tracking
* Does NOT share information with third parties
* Operates entirely locally on your WordPress installation
* Is 100% GDPR compliant

= What data does the plugin store? =

The plugin stores:
* **Settings** - Your configuration options (in wp_options table)
* **Post Meta** - Per-post auto-update status (in wp_postmeta table)
* **Logs** - Update history, last 100 entries (in wp_options table)

All data is stored in your WordPress database. No external databases used.

= Can users see update logs? =

No. Only administrators with 'publish_posts' capability can:
* Access plugin admin pages
* View update logs
* Configure settings
* Trigger manual updates

Regular site visitors and subscribers cannot access plugin functionality.

= Compatibility =

= Which WordPress versions are supported? =

* **Minimum**: WordPress 5.0
* **Tested up to**: WordPress 6.4
* **Recommended**: Latest stable WordPress version

The plugin uses only stable WordPress APIs, so it should work with future versions.

= Which PHP versions are supported? =

* **Minimum**: PHP 7.4
* **Recommended**: PHP 8.0 or higher
* **Tested**: PHP 7.4, 8.0, 8.1, 8.2, 8.3

Older PHP versions (5.6, 7.0-7.3) are NOT supported.

= Does this work with WPML or Polylang? =

Yes! The plugin works with multilingual plugins:
* Post selection works across all languages
* Logs show posts from all languages
* Settings are global (not per-language)
* Each language version of a post can be enabled/disabled independently

= Does this work with WooCommerce? =

Yes! You can auto-update:
* Product posts (product post type)
* Shop pages
* Product category pages

Enable "product" post type in Settings to include WooCommerce products.

= Does this work with custom post types from other plugins? =

Yes! The plugin automatically detects:
* All registered public post types
* Shows them in Settings page checkboxes
* You can enable any combination of post types

Works with ACF, CPT UI, Pods, Toolset, and all custom post type plugins.

== Screenshots ==

1. **Post Selector Interface** - Filterable table showing all posts with checkboxes and toggle switches. Select exactly which posts to auto-update with advanced filters by type, category, author, and status.

2. **Settings Page** - Comprehensive configuration panel with master enable/disable switch, update time scheduler, post type selection, and performance settings. System status section shows cron schedule and statistics.

3. **Update Logs** - Detailed history of all automatic updates with before/after dates, post titles, and timestamps. Export to CSV for record-keeping and analysis. Clear logs with confirmation dialog.

4. **Manual Trigger** - On-demand update interface with dry-run mode for testing. Real-time progress bar shows update status. Results summary displays successful updates and any errors.

5. **Dashboard Widget** - Quick stats display on WordPress dashboard showing posts marked for update, total updates performed, and recent log entries. Quick action buttons for easy access.

6. **Contextual Help** - Built-in help system with comprehensive FAQ, troubleshooting guide, use case examples, and keyboard shortcuts reference. Context-sensitive help tabs on every admin page.

7. **Email Notifications** - Optional email alerts for update completion or errors. Configure recipient address and enable/disable in settings.

8. **Bulk Actions** - Enable or disable auto-update for multiple posts at once. Smart selection tools include select all, select none, and select filtered posts.

== Changelog ==

= 1.0.0 - 2025-10-30 =

**Initial Release**

Features:
* Automated daily post date updates via WordPress cron
* Post selection interface with advanced filtering
* Filter by post type, category, author, and auto-update status
* Comprehensive settings page with configurable options
* Activity logging system with before/after dates
* Batch processing for large sites (configurable 1-1000 posts)
* Manual update trigger with dry-run mode
* Real-time AJAX toggle switches for individual posts
* Dashboard widget with quick statistics
* Email notification system (optional)
* CSV export for update logs
* Undo functionality for bulk actions (5-minute window)
* Keyboard shortcuts for power users
* Contextual help system with FAQ and troubleshooting
* Full accessibility support (ARIA labels and keyboard navigation)
* Multisite compatible
* Full internationalization support (i18n ready)
* Transient locking to prevent concurrent runs
* Memory-efficient batch processing
* Optimized database queries
* WordPress coding standards compliant
* Security audited (zero critical vulnerabilities)
* PHPUnit test suite included
* Comprehensive developer hooks and filters

Technical:
* Minimum WordPress 5.0, tested up to 6.4
* Minimum PHP 7.4, tested up to 8.3
* MySQL 5.7+ or MariaDB 10.2+ compatible
* Zero conflicts with original Bulk Datetime Change plugin
* Proper namespacing (adpr_ prefix)
* Performance optimized for 10,000+ posts
* OWASP Top 10 compliant
* GDPR compliant (no data collection)

Developer:
* Action hooks: adpr_before_update, adpr_after_update, adpr_cron_start, adpr_cron_complete
* Filter hooks: adpr_update_date, adpr_get_posts_for_update, adpr_batch_size
* Complete API documentation in HOOKS.md
* Configuration guide in CONFIGURATION.md
* Testing procedures in TESTING.md
* Security audit report in SECURITY.md

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and enjoy automated post date refreshing! Set it once and forget it - your posts will stay fresh automatically.

== Additional Information ==

= Links =

* **Plugin Homepage**: [https://example.com/auto-daily-post-refresher](https://example.com/auto-daily-post-refresher)
* **Documentation**: See CONFIGURATION.md for detailed setup guide
* **Developer API**: See HOOKS.md for hooks and filters documentation
* **Support Forum**: [https://wordpress.org/support/plugin/auto-daily-post-refresher](https://wordpress.org/support/plugin/auto-daily-post-refresher)
* **GitHub Repository**: [https://github.com/yourusername/auto-daily-post-refresher](https://github.com/yourusername/auto-daily-post-refresher)
* **Issue Tracker**: [https://github.com/yourusername/auto-daily-post-refresher/issues](https://github.com/yourusername/auto-daily-post-refresher/issues)
* **Rate this Plugin**: [https://wordpress.org/support/plugin/auto-daily-post-refresher/reviews/](https://wordpress.org/support/plugin/auto-daily-post-refresher/reviews/)

= Support =

For support:
1. Check the **built-in help** system in the plugin admin pages
2. Read the **FAQ section** above
3. Review **CONFIGURATION.md** for setup instructions
4. Visit the **WordPress.org support forum**
5. Submit issues on **GitHub** for bug reports

= Contributing =

This is an open-source project. Contributions are welcome:
* Submit pull requests on GitHub
* Report bugs in the issue tracker
* Suggest features and improvements
* Help with translations
* Write documentation

= Translation =

The plugin is translation-ready:
* POT file included in `/languages` directory
* Text domain: `auto-daily-post-refresher`
* All strings are internationalized
* RTL language support included

To translate:
1. Use Poedit or similar tool
2. Translate the .pot file to your language
3. Submit translation via WordPress.org
4. Or contribute via GitHub

= Credits =

* **Original Inspiration**: Bulk Datetime Change by Katsushi Kawamori
* **Architecture**: Based on WordPress Plugin Boilerplate patterns
* **Icons**: Dashicons from WordPress core
* **Testing**: WordPress PHPUnit testing framework

= Privacy Policy =

Auto Daily Post Refresher does not:
* Collect or store personal data
* Use cookies or tracking mechanisms
* Transmit any data to external servers
* Include third-party scripts or services
* Share information with any third parties

The plugin operates entirely within your WordPress installation using only:
* WordPress core functions and APIs
* Your WordPress database for settings storage
* Standard WordPress cron system for scheduling

All operations are local to your server. No external communication occurs.

This plugin is GDPR, CCPA, and privacy regulation compliant by design.

= License =

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

= Acknowledgments =

Special thanks to:
* WordPress community for the excellent documentation
* Katsushi Kawamori for the original Bulk Datetime Change plugin
* All beta testers and early adopters
* Contributors to the project

---

**Developed with care for the WordPress community. Happy automating!**
