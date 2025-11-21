# Evosus API Limitations - Critical Findings

**Date:** 2025-11-21
**API Version Tested:** 6.6.2XX and 6.7.XXX

## Summary

The Evosus `Customer_Order_Add` API endpoint has **fundamental limitations** that prevent syncing WooCommerce prices and tax rates. These parameters are **not supported by the official API specification**, despite the API accepting them without error.

## What We Tested

### Test 1: UnitPrice Override
**Expected:** Set custom price for line items
**Result:** ❌ **IGNORED** - Evosus uses catalog pricing
**Evidence:** Order #452975 sent with UnitPrice=$150, Evosus created order with $228.80 (default)

### Test 2: SalesTax_PK Override
**Expected:** Set custom tax rate per order
**Result:** ❌ **IGNORED** - Evosus uses customer's default tax rate
**Evidence:** Order sent with SalesTax_PK=2 (5% GST), Evosus used 13% HST (customer default)

## API Specification Analysis

### OrderLineItems Schema (Official Spec)
```javascript
OrderLineItems:
  type: object
  properties:
    ItemCode: string       // ✅ Supported
    Quantity: number       // ✅ Supported
    Comment: string        // ✅ Supported
    // UnitPrice: NOT IN SPEC ❌
```

### CustomerOrderAdd Schema (Official Spec)
```javascript
CustomerOrderAdd:
  required:
    - Customer_ID
    - BillTo_CustomerLocationID
    - ShipTo_CustomerLocationID
    - DistributionMethodID
    - ExpectedOrderTotal
  properties:
    Customer_ID: string
    BillTo_CustomerLocationID: string
    ShipTo_CustomerLocationID: string
    DistributionMethodID: string
    ExpectedOrderTotal: string
    PONumber: string
    Order_Note: string
    Internal_Note: string
    ServiceRequest_Note: string
    LineItems: array
    // SalesTax_PK: NOT IN SPEC ❌
```

## Impact on WooCommerce Integration

### ❌ What DOESN'T Sync
1. **Product Pricing**
   - WooCommerce sale prices → Ignored
   - WooCommerce discounts → Ignored
   - Custom pricing → Ignored
   - **Result:** Evosus uses its catalog prices

2. **Shipping Costs**
   - WooCommerce shipping fees → Ignored
   - **Result:** Evosus uses FREIGHTE2 default price

3. **Tax Rates**
   - Province-specific tax rates (BC=5%, ON=13%, etc.) → Ignored
   - **Result:** Evosus uses customer's default tax rate (13% HST for Ontario customers)

### ✅ What DOES Sync
1. **Line Items**
   - SKU (ItemCode)
   - Quantity
   - Product name (Comment)

2. **Order Metadata**
   - Customer ID
   - Billing/shipping addresses
   - Order notes
   - PO number (WooCommerce order #)

3. **Customer Data**
   - Customer creation/matching works correctly

## Solution Implemented

### Code Changes

1. **Removed UnitPrice from LineItems**
   - No longer sends UnitPrice (it was being ignored anyway)
   - Simplified line item structure to spec-compliant format

2. **Removed SalesTax_PK Logic**
   - Removed tax rate mapping code
   - Removed SalesTax_PK parameter from orders

3. **Added Warning System**
   - Logs warning about API limitations
   - Adds note to Evosus order with WooCommerce total
   - Internal note includes disclaimer about manual adjustment

### Example Internal Note (Added to Every Order)
```
WooCommerce Order ID: 16538 | Created via API on 2025-11-21 17:35:40

WARNING: Evosus API does not support custom pricing or tax rates.
- WC Total: $225.00 (WC tax: 5.0%)
- Evosus will use its own catalog pricing and customer's default tax rate
- Manual adjustment may be required if totals don't match
```

### Example Log Warning
```
API Limitation: Evosus will use its own pricing (not WC total $225.00)
and customer default tax (not WC rate 5.0%)
```

## Recommendations

### For Staff Workflow
1. **Review every synced order in Evosus**
   - Check if Evosus total matches WooCommerce total
   - Manually adjust pricing if needed
   - Manually adjust tax rate if customer is in different province

2. **High-Risk Orders** (likely to have mismatches)
   - Orders with discounts/coupons
   - Orders with sale prices
   - Orders from BC/AB/MB (5% GST instead of 13% HST)
   - Orders from Atlantic Canada (15% HST instead of 13%)
   - US orders (0% tax instead of 13%)

### For Future Consideration
1. **Calculate Expected Evosus Total**
   - Could use `Customer_Order_LineItem_Calculate` endpoint
   - Compare with WooCommerce total before syncing
   - Warn user if mismatch exceeds threshold

2. **Block Syncing for Large Discrepancies**
   - Don't sync if WC total differs from expected Evosus total by >10%
   - Force manual order entry for these cases

3. **Contact Evosus Support**
   - Request UnitPrice support in future API version
   - Request SalesTax_PK support for multi-province businesses
   - Explain legitimate use case (WooCommerce integration)

## Test Orders Created

| Order ID | Test Description | Result |
|----------|------------------|--------|
| 452975 | UnitPrice=$150, SalesTax_PK=2 | Both ignored, used defaults |
| 452826 | Freight item FREIGHTE2 | Works, but uses default freight price |

## Files Modified

```
evosus-sync-for-woocommerce/includes/class-wc-evosus-integration.php
  - Removed UnitPrice from prepare_line_items()
  - Removed get_evosus_tax_code() tax mapping
  - Added get_wc_tax_rate() for logging only
  - Added warning system to create_evosus_order()
  - Added WC total to Order_Note
  - Added detailed internal note with disclaimer
```

## Conclusion

**The Evosus API is not designed for e-commerce integrations where external systems control pricing and tax.**

It's designed for scenarios where:
- Evosus is the source of truth for pricing
- Customers have a single default tax rate
- Orders use standard catalog pricing

For your WooCommerce integration, this means:
- ✅ Orders CAN be synced successfully
- ⚠️ Totals WILL often mismatch (different prices/tax)
- 👤 Staff MUST review and manually adjust orders as needed

This is a **limitation of Evosus**, not a bug in the plugin.
