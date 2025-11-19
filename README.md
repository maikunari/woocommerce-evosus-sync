# WooCommerce Evosus Sync - Development Project

Enterprise integration plugin that syncs WooCommerce orders to Evosus Business Management Software.

## Project Structure

```
woocommerce-evosus-sync/
├── evosus-sync-for-woocommerce/   # WordPress plugin (ready to install)
├── tests/                          # API testing scripts
├── docs/                           # Documentation & specifications
├── scripts/                        # Build & deployment scripts
└── tasks.json                      # Project task management
```

## Quick Start

### Install Plugin
1. Zip the `evosus-sync-for-woocommerce/` directory
2. Upload to WordPress: Plugins → Add New → Upload Plugin
3. Activate and configure in WooCommerce → Settings → Evosus Sync

### Run Tests
```bash
# Test API connection
php tests/test-service-check.php

# Test full API functionality
php tests/test-evosus-api.php CompanySN Ticket

# Test inventory lookup
php tests/test-inventory.php
```

## API Credentials

**Live API:**
- CompanySN: `20060511100251-006`
- Ticket: `9b6547d3-f45a-482f-b264-2a616a6ec0fb`
- Base URL: `https://cloud3.evosus.com/api`

**Test Data:**
- SKU: `EF-161-A` (18 units available)
- Customer: `mike@friendlyfires.ca` (CustomerID: 6687)

## Documentation

- [API Testing Results](docs/API-TESTING-RESULTS.md) - Live API test outcomes
- [Current Status](docs/CURRENT_STATUS.md) - Development progress
- [Pre-Deployment Review](docs/PRE-DEPLOYMENT-REVIEW.md) - Security audit
- [Testing Strategy](docs/TESTING_STRATEGY.md) - Test plan & scenarios
- [Evosus API Spec](docs/evosus-api.js) - Complete API documentation

## Features

✅ **Order Sync** - Automatic WooCommerce → Evosus order creation
✅ **SKU Validation** - Real-time inventory checks
✅ **Customer Matching** - Automatic customer lookup/creation
✅ **Test Mode** - Safe dry-run before live sync
✅ **SKU Mapping** - Map WooCommerce SKUs to Evosus codes
✅ **Logging** - Comprehensive error tracking
✅ **Admin UI** - Settings & manual sync controls

## Development

**Requirements:**
- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+
- Evosus Business Management Software with Web API enabled

**Setup:**
1. Clone repository
2. Install plugin from `evosus-sync-for-woocommerce/`
3. Configure API credentials
4. Run test suite

## Testing Workflow

See [tasks.json](tasks.json) for complete testing checklist:

1. Configure plugin with API credentials
2. Create test product with known SKU
3. Create test order
4. Verify SKU validation
5. Dry run sync (test mode)
6. Live sync ONE order
7. Verify in Evosus dashboard
8. Document results

## Technical Details

**API Framework:** ServiceStack 3.971
**Evosus Version:** 6.8.3
**Architecture:** Hybrid cloud (routes to on-premise service)
**Authentication:** Query parameters (CompanySN + ticket)
**Request Format:** POST with JSON `{args: {...}}`

## Support

For issues or questions, see documentation in `docs/` directory.

## License

Custom development for enterprise client.

---

**Status:** ✅ API Testing Complete | ⏸️ WordPress Testing Pending
**Last Updated:** 2025-11-19
