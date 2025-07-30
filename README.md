# BBT Framer to WordPress

[![WordPress Plugin Version## 📖 Usage

### Step-by-Step Guide

1. **Upload CSV File**: From the importer page, upload your CSV file. The plugin will read the header row and present a mapping interface.

2. **Configure Field Mapping**: For each CSV column, select which post field it should populate or choose **Custom Field** to provide your own meta key. Leave columns unmapped if you don't need them.

3. **Set Options**: Check the **Skip posts with duplicate slugs** box if you want to ignore rows when a post with the same slug already exists.

4. **Start Import**: Click **Start Import**. The importer processes the file in batches and displays progress. When complete you'll see a summary of imported, skipped and failed rows.

5. **Handle Failures**: If any rows failed you can click **Retry Failed Rows** to attempt them again.

### CSV Format Requirements

Your CSV file should have:
- **Header row**: First row must contain column names
- **UTF-8 encoding**: Recommended for special characters
- **Proper escaping**: Quote fields containing commas or line breaks

### Example CSV Structure

```csv
title,content,excerpt,slug,featured_image_url,featured_image_alt,category,tags
"My First Post","This is the content of my first post.","A brief excerpt","my-first-post","https://example.com/image1.jpg","Alt text for image","Technology","wordpress,csv"
"Second Post","Content for the second post.","Another excerpt","second-post","https://example.com/image2.jpg","Alt text for second image","Tutorials","import,guide"
```

## 🛠️ Development

### Setting Up Development Environment

```bash
# Clone the repository
git clone https://github.com/bytesbrothers/bbt-framer-to-wordpress.git

# Navigate to plugin directory
cd bbt-framer-to-wordpress

# If you're working within a WordPress installation
# Copy to your plugins directory
cp -r . /path/to/wordpress/wp-content/plugins/bbt-framer-to-wordpress/
```

### File Structure

```
bbt-framer-to-wordpress/
├── bbt-framer-to-wordpress.php  # Main plugin file
├── framer-importer.css          # Admin styles
├── framer-importer.js           # Admin JavaScript
├── README.md                    # Documentation
├── license.txt                  # GPL v2 License
└── languages/                   # Translation files (if any)
```s://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![GitHub Issues](https://img.shields.io/github/issues/YOUR_GITHUB_USERNAME/bbt-framer-to-wordpress)](https://github.com/YOUR_GITHUB_USERNAME/bbt-framer-to-wordpress/issues)
[![GitHub Stars](https://img.shields.io/github/stars/YOUR_GITHUB_USERNAME/bbt-framer-to-wordpress)](https://github.com/YOUR_GITHUB_USERNAME/bbt-framer-to-wordpress/stargazers)

> A specialized WordPress plugin designed for seamlessly importing Framer content as blog posts via CSV files, with advanced features like image handling, custom field mapping, and batch processing.

BBT Framer to WordPress is a WordPress plugin specifically designed to import content from Framer projects as blog posts via CSV files. Originally created to bridge the gap between Framer designs and WordPress content management, this plugin excels at handling Framer-exported data and converting it into fully-featured WordPress Posts Importer and is maintained by **[Bytes Brothers](https://bytesbrothers.com)**. The plugin supports featured images, custom field mapping, duplicate detection and batch processing with progress feedback.

## 🚀 Quick Start

1. **Download** the latest release
2. **Install** via WordPress admin (Plugins → Add New → Upload Plugin)
3. **Navigate** to Tools → BBT Framer Importer
4. **Upload** your CSV file and start importing!BT Framer to WordPress

BBT Framer to WordPress is a WordPress plugin that imports blog posts from a CSV file into your site.  It’s a complete rebrand of the classic CSV Post Importer and is maintained by **Bytes Brothers**.  The plugin supports featured images, custom field mapping, duplicate detection and batch processing with progress feedback.

## ✨ Features

- **📁 Upload CSV**: Upload your CSV file via the WordPress admin and store it in the Media Library. The first row must contain column headers.
- **🔧 Flexible Mapping**: Map any CSV column to core post fields such as title, content, excerpt, slug, date, featured image URL and alt text. Unmapped columns can be stored as custom fields with your own meta key.
- **🖼️ Featured Images**: Provide a remote image URL and the plugin will download and attach it as the featured image for the new post. Alt text is saved as attachment meta.
- **📸 Additional Images**: Store extra image URLs in a custom field called `additional_images` for later processing.
- **🔍 Duplicate Handling**: Optionally skip rows when a post with the same slug already exists.
- **⚡ Batch Import via AJAX**: Large files are processed in batches of 10 rows to avoid timeouts and show real‑time progress. See the counters for imported, skipped and failed rows.
- **🔄 Retry Failures**: If any rows fail (for example, due to an unreachable image URL), you can retry importing just the failed rows.

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **File Permissions**: Write access to `/wp-content/uploads/`
- **Memory**: Sufficient PHP memory for processing large CSV files
- **Extensions**: PHP cURL extension for remote image downloads

## 📥 Installation

### Method 1: Download Release (Recommended)
1. Download the latest release from the [GitHub releases page](https://github.com/bytesbrothers/bbt-framer-to-wordpress/releases)
2. In your WordPress dashboard, go to **Plugins → Add New → Upload Plugin**
3. Select the ZIP file and click **Install Now**
4. When installation finishes, click **Activate**
5. Navigate to **Tools → BBT Framer Importer** to begin your import

### Method 2: Manual Installation
1. Download or clone this repository
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Tools → BBT Framer Importer**

## Usage

1. From the importer page, upload your CSV file.  The plugin will read the header row and present a mapping interface.
2. For each CSV column, select which post field it should populate or choose **Custom Field** to provide your own meta key.  Leave columns unmapped if you don’t need them.
3. Check the **Skip posts with duplicate slugs** box if you want to ignore rows when a post with the same slug already exists.
4. Click **Start Import**.  The importer processes the file in batches and displays progress.  When complete you’ll see a summary of imported, skipped and failed rows.  If any rows failed you can click **Retry Failed Rows** to attempt them again.

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### Reporting Issues
- 🐛 **Bug Reports**: [Open an issue](https://github.com/bytesbrothers/bbt-framer-to-wordpress/issues/new) with detailed steps to reproduce
- 💡 **Feature Requests**: Share your ideas for new features
- 📚 **Documentation**: Help improve our documentation

### Development Guidelines
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Include appropriate sanitization and escaping
- Test your changes thoroughly
- Write clear commit messages
- Update documentation as needed

### Pull Request Process
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Test thoroughly
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## 📝 Changelog

### Version 2.0
- Complete rebrand from CSV Post Importer
- Enhanced UI with progress feedback
- Improved error handling and retry functionality
- Better image processing capabilities
- Added support for additional images custom field

## ❓ Frequently Asked Questions

### How large CSV files can I import?
The plugin processes files in batches of 10 rows to avoid timeouts. File size limits depend on your server configuration, but the batch processing helps handle larger files.

### What happens if an image URL is broken?
Failed image downloads are tracked and reported. You can retry failed rows after fixing the image URLs.

### Can I import custom post types?
Currently, the plugin supports standard WordPress posts. Custom post type support may be added in future versions.

### Does it work with Gutenberg?
Yes, the plugin is compatible with both Classic Editor and Gutenberg Block Editor.

## 🆘 Support

- 📖 **Documentation**: Check this README and inline code comments
- 🐛 **Issues**: [GitHub Issues](https://github.com/bytesbrothers/bbt-framer-to-wordpress/issues)
- 💬 **Community**: [WordPress.org Support Forums](https://wordpress.org/support/)
- 🌐 **Website**: [Bytes Brothers](https://bytesbrothers.com)

## 📄 License

This plugin is released under the terms of the **GNU General Public License v2.0** or later. See the [LICENSE.txt](license.txt) file for details.

### What this means:
- ✅ You can use this plugin for any purpose
- ✅ You can study and modify the source code  
- ✅ You can redistribute the original or modified versions
- ⚠️ Any redistributed versions must also be GPL v2.0+
- ⚠️ No warranty is provided

---

## 🏢 About Bytes Brothers

**Bytes Brothers** is a web development company specializing in WordPress solutions, custom plugins, and digital experiences. We're committed to creating high-quality, open-source tools for the WordPress community.

- 🌐 **Website**: [bytesbrothers.com](https://bytesbrothers.com)
- 📧 **Contact**: info@bytesbrothers.com
- 🐙 **GitHub**: [@bytesbrothers](https://github.com/bytesbrothers)

---

<div align="center">

**Made with ❤️ by [Bytes Brothers](https://bytesbrothers.com)**

⭐ **Star this repository if you find it helpful!** ⭐

</div>