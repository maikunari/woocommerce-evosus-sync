swagger: '2.0'
info:
  version: "6.6.4XX"
  title: Evosus Web API
  description: "\nThis is the Evosus Web API for version 6.6.407 and greater of Evosus Business Management Software. If you're not using version 6.6.407 or greater, refer to the Evosus Web API documentation version 6.6.2XX(6.6.215 and 6.6.305), 6.6 (6.6.130 - 6.6.135) or 1.6(6.5.109 and below). \n\nAccess your Evosus data securely using simple integration methods from anywhere in the world.\n\nEvosus Web API is a licensed product of [Evosus, Inc.](http://www.evosus.com/)\n\nEach request requires at a minimum two pieces of information. The `CompanySN` and a `ticket`. The CompanySN corresponds to the unique serial number of your licensed installation of Evosus Business Management. The `ticket` value will be supplied by Evosus after your Web API account is created.\n\nIf you are not a licensed customer of Evosus, Inc. you may use the credentials for the demo account found below.\n### DEMO - Water World, Inc. Account\n\n```\n  CompanySN:  20101003171313*999\n  Ticket:     a71279ea-1362-45be-91df-d179925a0cb1\n  \n```\n"
  termsOfService: 'http://evosus.com'
  contact:
    name: API Support
    url: 'http://support.evosus.com'
  license:
    name: Evosus End-User License Agreement
    url: 'http://www.evosus.com/eula'
host: cloud3.evosus.com
basePath: /api
schemes:
  - https
  - http
produces:
  - application/json
consumes:
  - application/json
paths:
  /method/ServiceCheck:
    get:
      tags:
        - System
      description: Gets the status of the API service.
      summary: Checks health of Web API
      operationId: ServiceCheck
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
        - name: ticket
          in: query
          description: Session ticket
          required: true
          type: string
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples: 
            application/json:
              code: OK
              message: Success
              response: The service is running
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  /method/LoggedInUsers:
    get:
      tags:
        - System
      description: 'A list of users logged into Evosus Business Management. '
      summary: A list of logged in users
      operationId: LoggedInUsers
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
        - name: ticket
          in: query
          description: Session ticket
          required: true
          type: string
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/LoggedInUsers'
          examples:
            application/json:
              code: OK
              message: Success
              response: '[{"ID": "123", "Login": "12-07-2015 08:15:00", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  '/files/{filepath}':
    get:
      tags:
        - Files
      description: Gets a file description.
      summary: Get file description
      operationId: getFile
      parameters:
        - name: filepath
          in: path
          description: File path
          required: true
          type: string
          format: string
        - name: ForDownload
          in: query
          description: Download file
          required: false
          type: boolean
        - name: ticket
          in: query
          description: Session ticket
          required: false
          type: string
          format: string
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/File'
          examples:
            application/json:
              file:
                name: rVTQDLWNLPrDiJ0.jpg
                extension: .jpg
                fileSizeBytes: 1256410
                modifiedDate: Date(1432357845677)
                isTextFile: false
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  /method/Company_Get:
    get:
      tags:
        - Company
      description: 'Gets basic information about the company based using the CompanySN. For example, this method returns the following: company name, company address, contact name, phone number, fax, and email.'
      summary: Gets basic company information using CompanySN
      operationId: CompanyGet
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
        - name: ticket
          in: query
          description: Session ticket
          required: true
          type: string
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples: 
            application/json:
              code: OK
              message: Success
              response: The service is running
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  /method/Company_License_Get:
    get:
      tags:
        - Company
      description: 'Gets a list of product licenses using CompanySN.'
      summary: Gets a list of product licenses using CompanySN
      operationId: CompanyLicenseGet
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
        - name: ticket
          in: query
          description: Session ticket
          required: true
          type: string
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples: 
            application/json:
              code: OK
              message: Success
              response: The service is running
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
 
  /method/Customer_Search:
    post:
      tags:
        - Customers
      description: Returns a list of all customers that match a search criteria. For example, you can search customers by Customer_ID, name, address, phone number, email address, or your legacy system ID (DataConversion_LegacySystemID).
      summary: Customer Search
      operationId: CustomerSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerSearches'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "CompanyName": "John Doe", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerSearch'
          required: true
  /method/Customer_Add:
    post:
      tags:
        - Customers
      description: Adds a Customer/Lead.
      summary: Customer Add
      operationId: CustomerAdd
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: 16966
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerAdd'
          required: true
  /method/Customer_Note_Add:
    post:
      tags:
        - Customers
      description: Adds a note to a Customer/Lead using Customer_ID. To see the notes on a customer in the application, go to Customer > Open a customer > Notes tab. The Insert Date will automatically be assigned to the note, and the note is given a type of General.
      summary: Customer Note Add
      operationId: CustomerNoteAdd
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: 86189
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerNoteAdd'
          required: true
  /method/Customer_Addresses_Get:
    post:
      tags:
        - Customers
      description: 'Gets all of the addresses set up on a customer using using Customer_ID. To see the addresses set up on a customer in the application, go to Customer > Open a customer > Contact Info.'
      summary: Customer Address Lookup
      operationId: CustomerAddressGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerAddresses'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "CustomerLocationID": 123, ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerAddress'
          required: true
  /method/Customer_CreditMemos_Open_Get:
    post:
      tags:
        - Customers
      description: 'Gets a list of open credit memos on a customer account using using Customer_ID. In the application, you can see the open credit memos on a customer account using the Credit Memos tab on the Customer screen (Customer > Open a customer > Credit Memos tab).'
      summary: Gets a list of open credit memos on a customer account
      operationId: CustomerCreditMemosOpenGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerCreditMemosOpenGet'
            example:
              code: OK
              message: Success
              response: '[{"CreditMemo_PK": 123, "Store": "Main St.", "MemoNumber": "CRM0029637", "MemoDate": "7/6/2017", "Amount": 150, "CurrentBalance": 150, "Comment": "This is the comment on the credit memo", "Reason": "This is the reason selected when the credit memo is created"}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerCreditMemosOpenGet'
          required: true
  /method/Customer_CreditMemo_Apply:
    post:
      tags:
        - Customers
      description: 'Apply an open credit memo to an invoice. Use Customer_CreditMemos_Open_Get to get a list of open credit memos on a customer record.'
      summary: Apply an invoice to a credit memo.
      operationId: CustomerCreditMemoApply
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerCreditMemoApply'
            example:
              code: OK
              message: Success
              response: '[0]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerCreditMemoApply'
          required: true
  /method/Customer_Equipment_Get:
    post:
      tags:
        - Customers
      description: 'Gets all of the equipment on a customer record using Customer_ID. Equipment is added to a customer record using the Equipment tab on the Customer screen (Customer > Open a customer > Equipment tab).'
      summary: Gets a list of equipment on a customer record
      operationId: CustomerEquipmentGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerEquipmentGet'
            example:
              code: OK
              message: Success
              response: '[{"CustomerEquipment_PK": 123, "SerialNumber": 123, ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerEquipmentGet'
          required: true
  /method/Customer_Equipment_Add:
    post:
      tags:
        - Customers
      description: 'Add equipment to a customer record. Equipment is added to a customer record using the Equipment tab on the Customer screen (Customer > Open a customer > Equipment tab).'
      summary: Add equipment to a customer record
      operationId: CustomerEquipmentAdd
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerEquipmentAdd'
            example:
              code: OK
              message: Success
              response: '[{"Equipment_ID": 123}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerEquipmentAdd'
          required: true
  /method/Customer_Invoice_Search:
    post:
      tags:
        - Customers
      description: 'Produces a list of invoices for a specific customer using a Customer _ID,  begin, and end date. The begin and end date must be in a MM/DD/YY format, and cannot be more than 100 days apart.'
      summary: Customer Invoice Search
      operationId: CustomerInvoiceSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerInvoice'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "DocumentCategory": "Invoice", "DocumentCategory": "Invoice Sale", "DocumentType": "Invoice", "InvoiceNumber": "165937-1", "InvoiceTotal": "$60,239.93", "InvoiceDate": "7/6/2017", "InvoiceDueDate": "7/6/2017", "Status": "Open", "BalanceDue": "$60,189.93", "PONumber": "", "ContractName": "", "JobName": "", "BillTo_Company": "Portland Timbers", "BillTo_Contact": "Al Smith", "BillTo_Address1": "100 Victory Lane", "BillTo_Address2": "", "BillTo_City": "Portland", "BillTo_State": "OR", "BillTo_PostCode": "97203", "BillTo_Country": "US", "ShipTo_Company": "", "ShipTo_Contact": "Al Smith", "ShipTo_Address1": "100 Victory Lane", "ShipTo_Address2": "", "ShipTo_City": "Portland", "ShipTo_State": "OR", "ShipTo_PostCode": "97203", "ShipTo_Country": "US", "Instructions": "", "StoreName": "Administration"}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerInvoiceSearch'
          required: true
  /method/Customer_InvoiceDetail_Search:
    post:
      tags:
        - Customers
      description: Produces a line item detail of an invoice.
      summary: Customer Invoice Detail Search
      operationId: CustomerInvoiceDetailSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerInvoiceDetail'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "InvoiceNumber": "110543", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerInvoiceDetailSearch'
          required: true
  /method/Customer_Lead_Search:
    post:
      tags:
        - Customers
      description: "Produces a listing of active Leads in a date range. The date range cannot be more than 31 days, and the format of the date must be MM/DD/YY."
      summary: Customer Lead Search
      operationId: CustomerLeadSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerLeadSearches'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "CustomerInterestID": 15, "Name": "John Doe", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerLeadSearch'
          required: true
  /method/Customer_Lead_Status_Check:
    post:
      tags:
        - Customers
      description: 'For the combination of a Customer and Customer Interest (retrieved from the Customer_Lead_Search method), this method indicates if the customer is still a lead or not.'
      summary: Customer Lead Status Check
      operationId: CustomerLeadStatusCheck
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: string
            example:
              code: OK
              message: Success
              response: 'Yes'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerLeadStatusCheck'
          required: true
  /method/Customer_Notes_Get:
    post:
      tags:
        - Customers
      description: 'Gets a list of notes on a customer record between a BeginDate and EndDate using the Customer_ID. In the application, you can see the notes on a customer record if you go to Customer > Open a customer > Notes tab. The BeginDate and EndDate can be up to 190 days apart.'
      summary: Get notes on customer record
      operationId: CustomerNotesGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerNotesGet'
            example:
              code: OK
              message: Success
              response: '[{"ID": 123, "Service": false, "InsertDate": "1/31/2018 00:00:01 AM, "Employee": "Norton, Robert", "NotePreview": "POS Sale Retail for $170.93 at Administration", "Type": "Auto Note"}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerNotesGet'
          required: true
  /method/Customer_Order_Add:
    post:
      tags:
        - Customers
      description: 'Add an order to a customers profile. This interface supports a sales order or a service work order request. You will need the LocationID of both the billing and shipping address (Customer_Addresses_Get), the distribution method (Inventory_DistributionMethod_Search), and the item code (Inventory_Item_Search).'
      summary: Customer Order Add
      operationId: CustomerOrderAdd
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: string
            example:
              code: OK
              message: Success
              response: 12345
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerOrderAdd'
          required: true
  /method/Customer_Order_LineItem_Calculate:
    post:
      tags:
        - Customers
      description: "Calculate tax and the customer's price on an item. Tax is not always a straight-forward calculation and the customer who is purchasing may receive discounted pricing or a sales promotion at the time of purchase."
      summary: Customer Order LineItem Calculate
      operationId: CustomerOrderLineItemCalculate
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerOrderLineItemCalculates'
            example:
              code: OK
              message: Success
              response: '[{"Quantity": 2, "UnitPrice": 12.34, ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerOrderLineItemCalculate'
          required: true
  /method/Customer_Order_ServiceInterview_Search:
    post:
      tags:
        - Customers
      description: 'Produces a line item detail of an invoice. '
      summary: Customer Invoice Detail Search
      operationId: CustomerOrderServiceInteviewSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerOrderServiceInterview'
            example:
              code: OK
              message: Success
              response: '[{"ServiceQuestionID": "12", "InterviewName": "Cloudy Pool", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
  /method/Customer_Payment_Add:
    post:
      tags:
        - Customers
      description: "Use to add a deposit to an order or an unapplied payment to the customer's account. The order ID is optional. Not identifying an order will create a credit memo on the customer's account. "
      summary: Customer Payment Add
      operationId: CustomerPaymentAdd
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: string
            example:
              code: OK
              message: Success
              response: 15687
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerPaymentAdd'
          required: true
  /method/Customer_PaymentMethod_Search:
    post:
      tags:
        - Customers
      description: Use to lookup the PaymentMethodIDs that are in Evosus in order to support the Customer Payment Add method.
      summary: Customer PaymentMethod Search
      operationId: CustomerPaymentMethodSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerPaymentMethodSearch'
            example:
              code: OK
              message: Success
              response: '[{"PaymentMethodID": 1, "PaymentCategory": "Cash", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
  /method/Customer_Schedule_Search:
    post:
      tags:
        - Customers
      description: "Use to retrieve the customer's service and delivery schedule. "
      summary: Customer Schedule Search
      operationId: CustomerScheduleSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerSchedule'
            example:
              code: OK
              message: Success
              response: '[{"ScheduleID": 20952, "Date": "08/05/2015", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerScheduleSearch'
          required: true
  /method/Customer_SiteProfile_Get:
    post:
      tags:
        - Customers
      description: 'Gets a list of site profile attributes on a customer record using Customer_ID. In the application, you can see the site profile attributes on a customer record if you go to Customer > Open a customer > Site Profile tab > Open a site profile > Site tab. For example, you might have a site profile attribute that lists the volume of a pool. You can use this method to retrieve that value.'
      summary: Gets list of site profile attributes on customer record
      operationId: CustomerSiteProfileGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerSiteProfileGet'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "Customer": "Last Name, First Name", "Address1": "100 Main St.", "Address2": "Apt 1", "City": "Vancouver", "State": "WA", "PostCode": "98660", "SiteProfileType": "Pool", "Site": "(Customer > Open a customer > Site Profile tab > Open a site profile > Profile tab > Name field)", "SiteComment": "(Customer > Open a customer > Site Profile tab > Open a site profile > Profile tab > Comment field)", "SiteText": "(Customer > Open a customer > Site Profile tab > Open a site profile > Profile tab > Description field)", "Attribute": "Volume", "AttributeValue": "100000"}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerSiteProfileGet'
          required: true
  /method/Customer_Statement_Get:
    post:
      tags:
        - Customers
      description: "Use to retrieve the customer's statement for a date range. This will be the raw data used to format a customer statement, not an actual document. "
      summary: Customer Statement Get
      operationId: CustomerStatement
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerStatement'
            example:
              code: OK
              message: Success
              response: '[{"SortMe": "00001", "StoreName": "Burton Pools and Spas", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerStatementGet'
          required: true
  /method/Customer_Update:
    post:
      tags:
        - Customers
      description: "Update a customer name, bill to address, or ship to on an existing customer account using the Customer_ID. When using this method to add a new billing address, the billing address will automatically be set up as the default. You can also use this method to add an email address or phone number to a customer."
      summary: Customer Update
      operationId: CustomerUpdate
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/CustomerUpdate'
            example:
              code: OK
              message: Success
              response: '[{"CustomerID": 123, "CompanyName": "John Doe", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsCustomerUpdate'
          required: true
  /method/Employee_Search:
    post:
      tags:
        - Employee
      description: Use to retrieve the data set of active employees. Employee Login name will be used as an input for some other web methods. You can also use this method to get information about a specific employee usnig LoginName.
      summary: Employee Search
      operationId: EmployeeSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/Employee'
            example:
              code: OK
              message: Success
              response: '[{"FirstName": "Adam", "LastName": "Anderson", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsEmployeeSearch'
          required: false
  /method/Employee_Task_Add:
    post:
      tags:
        - Employee
      description: 'Use to send task/message to an employee or multiple employees. This method adds a task to the Employee Action  Item Search screen (Employee > My Action Items). This method does not validate that the customer number is valid before creating the task. If you use this method to create a task with an invalid customer ID number, users in Evosus Business Enterprise will get the following error when opening the task "Customer ID does not exist for parameter Customer ID - XXX".'
      summary: Employee Task Add
      operationId: EmployeeTaskAdd
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: string
            example:
              code: OK
              message: Success
              response: 20548
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsEmployeeTaskAdd'
          required: true
  /method/Inventory_Vendor_Search:
    post:
      tags:
        - Inventory
      description: 'Use to retrieve a data set of active Vendors that are associated with inventory items. You can use this method to retrieve a specific vendor by Vendor_ID or Name, or you can leave the Vendor_ID and Name parameters blank to retrieve a list of all vendors.' 
      summary: Inventory Vendor Search
      operationId: InventoryVendorSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/Vendor'
            example:
              code: OK
              message: Success
              response: '[{"VendorID": 15, "Name": "ABC Central Block and Brick", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsInventoryVendorSearch'
          required: true
  /method/Inventory_ProductLine_Search:
    post:
      tags:
        - Inventory
      description: Use to retrieve a data set of inventory product lines.
      summary: Inventory ProductLine Search
      operationId: InventoryProductLineSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/ProductLine'
            example:
              code: OK
              message: Success
              response: '[{"ProductLineID": 15, "ProductLine": "01 Above Ground Pools", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
  /method/Inventory_DistributionMethod_Search:
    post:
      tags:
        - Inventory
      description: Use to retrieve a data set of active Distribution Methods. The Distribution Method ID is a required input parameter for submitting an Order.
      summary: Inventory Distribution Method Search
      operationId: InventoryDistributionMethodSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/DistributionMethod'
            example:
              code: OK
              message: Success
              response: '[{"DistributionMethodID": 1, "Distribution Method": "Customer Pickup"}, ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
  /method/Inventory_Item_Get:
    post:
      tags:
        - Inventory
      description: 'Use to retrieve a specific item. This can be used to update the cost of an item in an external database. '
      summary: Inventory Item Get
      operationId: InventoryItemGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/Item'
            example:
              code: OK
              message: Success
              response: '[{"ProductLineID": 15, "ProductLine": "01 Above Ground Pools", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
          default: '20091102165026*177'
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
          default: 53dd22a7-12ba-4f94-986f-fe140018c4f7
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsInventoryItemGet'
          required: true
  /method/Inventory_Item_StockSiteQuantity_Get:
    post:
      tags:
        - Inventory
      description: For a specific Item use to retrieve the quantities per stock.
      summary: Inventory Item StockSite Quantity Get
      operationId: InventoryItemStockSiteQuantityGet
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/ItemStockSite'
            example:
              code: OK
              message: Success
              response: '[{"Code": "GPC-70-1103", "Description": "18 ft RD Winter Cover", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Template for the args parameter
          schema:
            $ref: '#/definitions/ArgsInventoryItemStockSiteQuantityGet'
          required: false
  /method/Inventory_Item_Search:
    post:
      tags:
        - Inventory
      description: 'Retrieve items base on specific filter. The request must include a ProductLineID(Inventory_ProductLine_Search), and also one of the following: vendorID (Inventory_Vendor_Search), '
      summary: Inventory Item Search
      operationId: InventoryItemSearch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/Item'
            example:
              code: OK
              message: Success
              response: '[{"Code": "GPC-70-1103", "Description": "18 ft RD Winter Cover", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsInventoryItemSearch'
          required: true
  /method/Orders_Open_Search:
    post:
      tags:
        - Orders/Quotes
      description: "List of open orders. CustomerID is optional."
      summary: "List of open orders, including return orders. Include a CustomerID in the request to get the orders for a specific customer."
      operationId: OrdersOpenSearch
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: '[{"OrderId": 167247,"Customer": "Smith, Amos","Interest": "Retail","Status": "Open-Order","PoNo": "Contract 2017-10","QuoteDate": "1/1/1900","ExpireDate": "12:00:00 AM","Total": 62.44,"MainItem": "Filter- Sorrento, Cumberland, Kauai & Martinique","Flag": "New","Employee": "EvosusAdmin","Store": "Store - Jamison","BillContact": "Amos Smith","BillAddr": "510 Lafayette Road ","BillCity": "Salem","BillState": "PA","BillPost": "19006","ShipContact": "Amos Smith","ShipAddr": "510 Lafayette Rd ","ShipCity": "Salem","ShipState": "PA","ShipPost": "19006","ShipPhone": "(474) 300-1000","ShipEmail": "","CustomerId": 21953,"CustomerType": "Spa Customer 10%","Distribution": "In House"}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsOrdersOpenSearch'
          required: false
  /method/Orders_Closed_Search:
    post:
      tags:
        - Orders/Quotes
      description: "List of closed orders filtered by date range that cannot exceed 180 days. CustomerID is optional."
      summary: "Closed orders filtered by date range. CustomerID optional."
      operationId: OrdersClosedSearch
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: [{"OrderId": 56304,"Customer": "Green, Jim","Interest": "Spa Covers","Status": "Closed-Invoice-Full","PoNo": "","QuoteDate": "2/3/2009","ExpireDate": "1/1/1900","Total": 461.1,"MainItem": "Cover- Classic 1988-1989 Rust","Flag": "Complete","Employee": "Lilly, Susan","Store": "Service Division","BillContact": "Jim Green","BillAddr": "321 Cedar Drive ","BillCity": "Portland","BillState": "PA","BillPost": "19005","ShipContact": "Jim Green","ShipAddr": "321 Cedar Drive ","ShipCity": "Portland","ShipState": "PA","ShipPost": "19005","ShipPhone": "(101) 914-0761","ShipEmail": "meanjimngreen@email.com","CustomerId": 10205,"CustomerType": "Service Customer 5%","Distribution": "Customer Pickup"}]
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsOrdersClosedSearch'
          required: true
  /method/Quotes_Open_Search:
    post:
      tags:
        - Orders/Quotes
      description: "List of open quotes. CustomerID is optional."
      summary: "List of open quotes. CustomerID is optional."
      operationId: QuotesOpenSearch
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: [{"OrderId": 167489,"Customer": "Smith, Bruce","Interest": "Retail","Status": "Open-Quote","PoNo": "","QuoteDate": "9/11/2017","ExpireDate": "10/11/2017","Total": 1012.5,"MainItem": "Illuminator Quartz Halogen 400 Port Capacity","Flag": "New","Employee": "EvosusAdmin","Store": "Administration","BillContact": "Bruce Smith","BillAddr": "100 Bills St ","BillCity": "Buffalo","BillState": "NY","BillPost": "19115","ShipContact": "Bruce Smith","ShipAddr": "100 Bills St ","ShipCity": "Buffalo","ShipState": "NY","ShipPost": "19115","ShipPhone": "(724) 200-0007","ShipEmail": "brucesmith72@email.com","CustomerId": 12747,"CustomerType": "Spa Customer 10%","Distribution": "Customer Pickup"}]
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsQuotesOpenSearch'
          required: false
  /method/Quotes_Closed_Search:
    post:
      tags:
        - Orders/Quotes
      description: "List of closed quotes filtered by a date range that cannot exceed 180 days. CustomerID is optional."
      summary: "Closed quotes filtered by date range. CustomerID optional."
      operationId: QuotesClosedSearch
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: [{"OrderId": 56491,"Customer": "Woods, Dave","Interest": "Service","Status": "Closed-Expired","PoNo": "","QuoteDate": "2/12/2009","ExpireDate": "3/14/2009","Total": 1452.26,"MainItem": "Pump: 2.5 Hp 2sp 230 V 60hz *replaced 6500-761 Need 6000-532 To Mount","Flag": "Complete","Employee": "Lilly, Susan","Store": "Service Division","BillContact": "Dave Woods","BillAddr": "13 Michael Way ","BillCity": "Pittsburg","BillState": "PA","BillPost": "19006","ShipContact": "Dave Woods","ShipAddr": "13 Michael Way ","ShipCity": "Pittsburg","ShipState": "PA","ShipPost": "19006","ShipPhone": "(215) 800-4002","ShipEmail": "dwoods@email.net","CustomerId": 7485,"CustomerType": "Retail Customer 5%","Distribution": "In House"}]
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsQuotesClosedSearch'
          required: true
  /method/OrderQuote_Items_Get:
    post:
      tags:
        - Orders/Quotes
      description: "List of items on a quotes or order."
      summary: "Items on quote or order"
      operationId: OrderQuoteItemsGet
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples:
            application/json:
              code: OK
              message: Success
              response: [{"CustomerID": "12747","OrderID": "167489","Line": "1","ItemCode": "2004","ItemDescription": "ILLUMINATOR QUARTZ HALOGEN 400 PORT CAPACITY","Quantity": "1","UnitPrice": "946.2634","Discount": "0","SubTotal": "946.2634","Tax": "66.24","Total": "1012.5034","Comment": "","Status": "Open - Quoted","Delivery": ""}]
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsOrderQuoteItemsGet'
          required: true
  
  /method/Product_Interests:
    post:
      tags:
        - Product Interests
      description: 'Gets a list of all active product interests. You can use this method in conjunction with the Customer_Add to get a list of product interests, and then add the interest to the customer/lead.'
      summary: Gets list of active product interests
      operationId: ProductInterests
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
        - name: Ticket
          in: query
          description: Session ticket
          required: true
          type: string
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples: 
            application/json:
              code: OK
              message: Success
              response: '[ {"ProductInterest_PK": 1, "Name": "Hot Tub", "Active": 1, "InsertEmployee_FK": 1, "InsertDate": "12/26/2018 10:26:24 AM", "UpdateEmployee_FK": 1, "UpdateDate": "12/26/2018 10:26:24 AM"}, {"ProductInterest_PK": 2, "Name": "Gazebo", "Active": 1, "InsertEmployee_FK": 1, "InsertDate": "4/5/2018 5:38:54 PM", "UpdateEmployee_FK": 17,  "UpdateDate": "6/18/201807 9:31:18 AM"}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  
    
  /method/TaxCodes_Get:
    post:
      tags:
        - Tax Codes
      description: 'Gets all of the tax codes for a company using CompanySN.'
      summary: Gets basic company information using CompanySN
      operationId: TaxCodesGet
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
        - name: Ticket
          in: query
          description: Session ticket
          required: true
          type: string
      responses:
        '200':
          description: Success
          schema:
            $ref: '#/definitions/Response'
          examples: 
            application/json:
              code: OK
              message: Success
              response: '[{"SalesTax_PK": "1", "TaxCode": "Exempt", "TaxAuthority": "Exempt", "TaxRate": 0, "TaxType": "Sales Tax", "DoNotApplyTaxLAbor": 0, "DoNotApplyTaxNonStock": 0, "DoNotApplyTaxOther": 0, "DoNotApplyTaxSPO": 0, "DoNotApplyTaxStock": 0, "RoundUpFractCent": 0, "PrintMessage": "", "MaxItemPriceToTax": null, "MinItemPriceToTax": 0}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  /method/TimecardPunches:
    post:
      tags:
        - Timecard
      description: 'Returns a list of timecard punch activity for a single employee on a given date. This method can be used to find the timecard punches for any day, not just the current date.'
      summary: List timecard activity for today
      operationId: TimecardPunches
      parameters:
        - name: CompanySN
          in: query
          description: Company serial number
          required: true
          type: string
          format: string
          default: '20091102165026*177'
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
          default: 53dd22a7-12ba-4f94-986f-fe140018c4f7
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsTimecardPunches'
          required: true
      responses:
        '200':
          description: Success
          schema:
            type: object
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/TimecardPunchAction'
            example:
              code: OK
              message: Success
              response: '[{"Action": "PunchIn", "PunchTime": "01-01-1900 08:15:00", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
  /method/TimecardPunch:
    post:
      tags:
        - Timecard
      description: 'Creates a Timecards Punch for the current date. The punch can be either a punch in or punch out, and this is defined using Status. The employee must be set up as a timekeeper in Evosus Business Management Software (Administration > Timecards > General Setup > Timekeeper Setup). The response includes all punches on the InPunchDate, including the punch that you just created. For example, if an employee already punched in at the beginning of the day and now punches out for lunch, the response will include the punch at the beginning of the day and the punch out for lunch.'
      summary: Punch In or Punch Out timecard for today
      operationId: TimecardPunch
      responses:
        '200':
          description: Success
          schema:
            type: object
            title: Response
            properties:
              code:
                type: string
              message:
                type: string
              response:
                type: array
                items:
                  $ref: '#/definitions/TimecardPunchAction'
            example:
              code: OK
              message: Success
              response: '[{"Action": "PunchIn", "PunchTime": "01-01-1900 08:15:00", ...}]'
        '400':
          description: Error
          schema:
            $ref: '#/definitions/ErrorResponse'
          examples:
            application/json:
              code: ER
              message: Error
      parameters:
        - name: CompanySN
          in: query
          type: string
          description: Company serial number
          required: true
        - name: ticket
          in: query
          type: string
          description: Session ticket
          required: true
        - name: body
          in: body
          description: Additional data for method
          schema:
            $ref: '#/definitions/ArgsTimecardPunch'
          required: true
definitions:
  Response:
    type: object
    properties:
      code:
        type: string
      message:
        type: string
      response:
        type: string
  ErrorResponse:
    type: object
    properties:
      code:
        type: string
      message:
        type: string
  File:
    description: File description from Files api call.
    type: object
    required:
      - name
    properties:
      name:
        $ref: '#/definitions/FileInfo'
  FileInfo:
    type: object
    properties:
      name:
        type: string
      extension:
        type: string
      fileSizeBytes:
        type: integer
        format: int32
      modifiedDate:
        type: string
      isTextFile:
        type: boolean
  ArgsTimecardPunch:
    description: The request object for the TimecardPunch method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/TimecardPunch'
  TimecardPunch:
    description: Use to populate args body parameter for TimecardPunches method
    type: object
    required:
      - Status
      - Username
      - Password
      - InPunchDate
    properties:
      Status:
        type: string
        description: 'PunchIn or PunchOut'
      Username:
        type: string
      Password:
        type: string
      InPunchDate:
        type: string
        description: 'Format date/time as yyyy-MM-dd HH:mm:ss, ex: 2015-03-30 18:30:00 - This is the date and time of the punch that you are creating.'
  TimecardPunchAction:
    description: A specific timecard punch action
    type: object
    properties:
      Action:
        type: string
      PunchTime:
        type: string
        description: 'Date will be formatted as 1/1/1900 HH:mm:ss AMPM, ex 1/1/1900 7:19:00 AM'
  ArgsTimecardPunches:
    description: The request object for the TimecardPunches method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/TimecardPunches'
  TimecardPunches:
    description: Use to populate args body parameter for TimecardPunches method
    type: object
    required:
      - Username
      - Password
      - InPunchDate
    properties:
      Username:
        type: string
      Password:
        type: string
      InPunchDate:
        type: string
        description: 'Format date as yyyy-MM-dd, ex: 2015-03-27'
  LoggedInUsers:
    description: User login information
    type: object
    properties:
      ID:
        type: number
      Login:
        type: string
      Employee:
        type: string
      Product:
        type: string
      Elapsed:
        type: string
      Idle:
        type: string
      MachineName:
        type: string
      HeldUntil:
        type: string
  ArgsCustomerAddress:
    description: The request object for the Customer_Address_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerAddress'
  CustomerAddress:
    description: Use to populate args body parameter for Customer_Address_Get method
    type: object
    required:
      - Customer_ID
    properties:
      Customer_ID:
        type: integer
  CustomerAddresses:
    description: Customer address information
    type: object
    properties:
      CustomerID:
        type: string
      CustomerLocationID:
        type: string        
      IsDefaultBillTo:
        type: string
      IsDefaultShipTo:
        type: string
      LocationName:
        type: string
      Contact:
        type: string
      Address1:
        type: string
      Address2:
        type: string
      City:
        type: string
      State:
        type: string
      PostCode:
        type: string
      Country:
        type: string
      Latitude:
        type: string
      Longitude:
        type: string
                  
  ArgsCustomerCreditMemosOpenGet:
    description: The request object for the Customer_CreditMemos_Open_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerCreditMemosOpenGet'
  CustomerCreditMemosOpenGet:
    description: 'Use to populate args body parameter for Customer_CreditMemos_Open_Get method'
    type: object
    required:
      - Customer_ID
    properties:
      Customer_ID:
        type: integer
  ArgsCustomerCreditMemoApply:
    description: The request object for the Customer_CreditMemo_Apply method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerCreditMemoApply'
  CustomerCreditMemoApply:
    description: Use to populate args body parameter for Customer_CreditMemo_Apply method
    type: object
    required:
      - CreditMemo_ID
      - SalesInvoice_ID
      - ApplyAmount
    properties:
      CreditMemo_ID:
        type: integer
      SalesInvoice_ID:
        type: integer
      ApplyAmount:
        type: string
        description: 'For example, 50.00'
  ArgsCustomerEquipmentGet:
    description: The request object for the Customer_Equipment_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerEquipmentGet'
  CustomerEquipmentGet:
    description: Use to populate args body parameter for Customer_Equipment_Get method
    type: object
    required:
      - Customer_ID
    properties:
      Customer_ID:
        type: integer
  CustomerEquipmentAdd:
    description: Use to populate args body parameter for Customer_Equipment_Add method
    type: object
    required:
      - Customer_ID
    properties:
      Customer_ID:
        type: integer
      Make:
        type: string
      Model:
        type: string
      Year:
        type: string
      SerialNumber:
        type: string
      Comment:
        type: string
      InstallDate:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      ItemCode:
        type: integer
        description: 'This must match the ItemCode of an existing inventory item. You can see a list of inventory items in the application if you go to Administration > Inventory > Search Items.'
  ArgsCustomerEquipmentAdd:
    description: The request object for the Customer_Equipment_Add method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerEquipmentAdd'
  ArgsCustomerSearch:
    description: The request object for the Customer_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerSearch'
  CustomerSearch:
    description: Use to populate args body parameter for Customer_Search method
    type: object
    properties:
      Customer_ID:
        type: integer
      PhoneNumber_List:
        type: string
        description: 'Format: XXXXXXXXXX. You can add several phone numbers to a customer record(home, cell, business, etc.). A customer is included in the search results if any phone number associated with the customer record matches the search criteria. The search criteria does not have to be the default phone number set up on the customer, but the search results only include the default phone number. In Evosus Business Management Software, phone numbers are added to customers using the Contact Info tab on the Customer screen (Customer > Open a customer > Contact Info tab).'
      EmailAddress_List:
        type: string
        description: 'This is just like PhoneNumber_List. You can add several email addresses to a customer record. A customer is included in the search results if any of the emails associated with the customer record match the search criteria. In Evosus Business Management Software, emails are added to customers using the Contact Info tab on the Customer screen (Customer > Open a customer > Contact Info tab).'
      Name:
        type: string
        description: 'Includes the custome first and last name.'
      Address1:
        type: string
      DataConversion_LegacySystemID:
        type: string
  CustomerSearches:
    description: Customer Search
    type: object
    properties:
      CustomerID:
        type: integer
      CompanyName:
        type: string
      LastName:
        type: string
      FirstName:
        type: string
      DisplayName:
        type: string
      BillTo_Contact:
        type: string
      BillTo_Address1:
        type: string
      BillTo_Address2:
        type: string
      BillTo_City:
        type: string
      BillTo_State:
        type: string
      BillTo_PostCode:
        type: string
      BillTo_Country:
        type: string
      ShipTo_Contact:
        type: string
      ShipTo_Address1:
        type: string
      ShipTo_Address2:
        type: string
      ShipTo_City:
        type: string
      ShipTo_State:
        type: string
      ShipTo_PostCode:
        type: string
      ShipTo_Country:
        type: string
      Default_Phone:
        type: string
      Default_Email:
        type: string
      Mobile_Phone:
        type: string
      BalanceDue:
        type: string
      FirstSaleDate:
        type: string
      LastSaleDate:
        type: string
      NumberOfSales:
        type: string
      LifetimeSalesTotal:
        type: string
      FirstServiceDate:
        type: string
      LastServiceDate:
        type: string
      NumberOfService:
        type: string
      LifetimeServiceTotal:
        type: string
      CustomerType:
        type: string
      DataConversion_LegacySystemID:
        type: string
      AustralianBusinessNumber:
        type: string
      EvosusInteralUse_SearchParmMatches:
        type: integer
  ArgsCustomerAdd:
    description: The request object for the Customer_Add method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerAdd'
  CustomerAdd:
    description: Use to populate args body parameter for Customer_Add method
    type: object
    required:
      - BillTo_Address1
    properties:
      Name_Company:
        type: string
      Name_First:
        type: string
      Name_Last:
        type: string
      BillTo_ContactName:
        type: string
      BillTo_Address1:
        type: string
      BillTo_City:
        type: string
      BillTo_StateAbbr:
        type: string
      BillTo_PostCode:
        type: string
      BillTo_Country:
        type: string
        description: 'Enter the entire country name - for example, enter United States, Canada, Australia.' 
      ShipTo_ContactName:
        type: string
      ShipTo_Address1:
        type: string
      ShipTo_Address2:
        type: string
      ShipTo_City:
        type: string
      ShipTo_StateAbbr:
        type: string
      ShipTo_PostCode:
        type: string
      ShipTo_Country:
        type: string
        description: 'Enter the entire country name - for example, enter United States, Canada, Australia.' 
      PhoneNumber_Mobile1:
        type: string
      PhoneNumber_Mobile2:
        type: string
      PhoneNumber_Home:
        type: string
      PhoneNumber_Work:
        type: string
      PhoneNumber_Fax:
        type: string
      EmailAddress1:
        type: string
      EmailAddress2:
        type: string
      Customer_TypeName:
        type: string
        description: 'Customer types define customer classifications - for example, Commercial, Residential, or Wholesale. To see a list of customer types in the application, go to Administration > Accounting > General Setup > Customer Types'
      Lead_InterestName:
        type: string
        description: 'Marketing interests link a lead and their purchases to a specific interest. Use method Product_Interests to get an list of active interests. To see a list of lead interests in the application, go to Administration > Marketing > General Setup > Interests.'
      Lead_AdvertisingCode:
        type: string
      Lead_EmployeeEvosusLoginName:
        type: string
      Lead_StoreName:
        type: string
      Lead_Date:
        type: string
      WaterTest_Integration_SystemID:
        type: string
      WaterTest_Integration_CustomerID:
        type: string
      DataConversion_LegacySystemID:
        type: string
      AustralianBusinessNumber:
        type: string
      CheckCustomerDuplicates:
        type: string
        description: 'TRUE or FALSE - TRUE if this parameter is missing'
      CustomerNoteText:
        type: string
        description: 'Add a customer note as you create the customer record'
  ArgsCustomerNotesGet:
    description: The request object for the Customer_Notes_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerNotesGet'
  CustomerNotesGet:
    description: Use to populate args body parameter for Customer_Notes_Get method
    type: object
    required:
      - Customer_ID
      - BeginDate
      - EndDate
    properties:
      Customer_ID:
        type: integer
      BeginDate:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      EndDate:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  ArgsCustomerSiteProfileGet:
    description: The request object for the Customer_SiteProfile_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerSiteProfileGet'
  CustomerSiteProfileGet:
    description: Use to populate args body parameter for Customer_SiteProfile_Get method
    type: object
    required:
      - Customer_ID
    properties:
      Customer_ID:
        type: integer
  CustomerUpdate:
    description: Use to 
    type: object
    properties:
      Customer_ID:
        type: integer
        description: '3'
      Name_Company:
        type: string
        description: ''
      Name_First:
        type: string
        description: 'Clover'
      Name_Last:
        type: string
        description: 'Holton'
      BillTo_ContactName:
        type: string
        description: 'Riley Holton'
      BillTo_Address1:
        type: string
        description: '833 Main St'
      BillTo_Address2:
        type: string
        description: 'Apt #1'
      BillTo_City: 
        type: string
        description: 'Vancouver'
      BillTo_State:
        type: string
        description: 'WA'
      BillTo_PostCode:
        type: string
        description: '98660'
      BillTo_Country:
        type: string
        description: 'Unites States'
      ShipTo_ContactName:
        type: string
        description: ''
      ShipTo_Address1:
        type: string
        description: '833 Main St'
      ShipTo_Address2:
        type: string
        description: 'Apt #1'
      ShipTo_City: 
        type: string
        description: 'Vancouver'
      ShipTo_State:
        type: string
        description: 'WA'
      ShipTo_PostCode:
        type: string
        description: '98660'
      ShipTo_Country:
        type: string
        description: 'United States'
      Phone_Default:
        type: string
        description: "1231231234 - This phone number is automatically set up as the customer's default phone number, and it is assigned a default type of 'Cell'"
      Email_Default:
        type: string
        description: "email@email.com - This email address is automatically set up as the customer's default email address"
  ArgsCustomerUpdate:
    description: The request object for the Customer_Update method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerUpdate'
  ArgsCustomerNoteAdd:
    description: The request object for the Customer_Note_Add method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerNoteAdd'
  CustomerNoteAdd:
    description: Use to populate args body parameter for Customer_Note_Add method
    type: object
    required:
      - Customer_ID
      - NoteText
    properties:
      Customer_ID:
        type: integer
      NoteText:
        type: string
  ArgsCustomerInvoiceSearch:
    description: The request object for the Customer_Invoice_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerInvoiceSearch'
  CustomerInvoiceSearch:
    description: Use to populate args body parameter for Customer_Invoice_Search method
    type: object
    required:
      - Customer_ID
      - Begin_Date
      - End_Date
    properties:
      Customer_ID:
        type: integer
      Begin_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      End_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  CustomerInvoice:
    description: List of customer invoices
    type: object
    properties:
      CustomerID:
        type: string
      DocumentCategory:
        type: string
      DocumentType:
        type: string
      InvoiceNumber:
        type: string
      InvoiceTotal:
        type: string
      InvoiceDate:
        type: string
      InvoiceDueDate:
        type: string
      Status:
        type: string
      BalanceDue:
        type: string
      PONumber:
        type: string
      ContractName:
        type: string
      JobName:
        type: string
      BillTo_Company:
        type: string
      BillTo_Contact:
        type: string
      BillTo_Address1:
        type: string
      BillTo_Address2:
        type: string
      BillTo_City:
        type: string
      BillTo_State:
        type: string
      BillTo_PostCode:
        type: string
      BillTo_Country:
        type: string
      ShipTo_Company:
        type: string
      ShipTo_Contact:
        type: string
      ShipTo_Address1:
        type: string
      ShipTo_Address2:
        type: string
      ShipTo_City:
        type: string
      ShipTo_State:
        type: string
      ShipTo_PostCode:
        type: string
      ShipTo_Country:
        type: string
      Instructions:
        type: string
      StoreName:
        type: string
  ArgsCustomerInvoiceDetailSearch:
    description: The request object for the Customer_InvoiceDetail_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerInvoiceDetailSearch'
  CustomerInvoiceDetailSearch:
    description: Use to populate args body parameter for Customer_InvoiceDetail_Search method
    type: object
    required:
      - Customer_ID
      - InvoiceNumber
    properties:
      Customer_ID:
        type: integer
      InvoiceNumber:
        type: integer
  CustomerInvoiceDetail:
    description: List of invoices line items
    type: object
    properties:
      CustomerID:
        type: string
      InvoiceNumber:
        type: string
      Line:
        type: string
      ItemCode:
        type: string
      ItemDescription:
        type: string
      Quantity:
        type: string
      UnitPrice:
        type: string
      Discount:
        type: string
      SubTotal:
        type: string
      Tax:
        type: string
      Total:
        type: string
      Comment:
        type: string
      Status:
        type: string
      Delivery:
        type: string
  CustomerOrderServiceInterview:
    description: List of service interviews
    type: object
    properties:
      ServiceQuestionID:
        type: string
      InterviewName:
        type: string
      Question:
        type: string
      QuestionDetail:
        type: string
      QuestionOrder:
        type: string
  ArgsCustomerLeadSearch:
    description: The request object for the Customer_Lead_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerLeadSearch'
  CustomerLeadSearch:
    description: Use to populate args body parameter for Customer_Lead_Search method
    type: object
    required:
      - Begin_Date
      - End_Date
    properties:
      Begin_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      End_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  CustomerLeadSearches:
    description: Customer Lead Search
    type: object
    properties:
      CustomerID:
        type: integer
      CustomerInterestID:
        type: integer
      Name:
        type: string
      Inquired:
        type: string
      Interest:
        type: string
      Flag:
        type: string
      Activity:
        type: string
      LastActivityDaysAgo:
        type: string
      BillTo_Address1:
        type: string
      BillTo_Address2:
        type: string
      BillTo_City:
        type: string
      BillTo_State:
        type: string
      BillTo_PostCode:
        type: string
      Phone:
        type: string
      Email:
        type: string
      Advertising:
        type: string
      Level:
        type: string
      Store:
        type: string
      ResponsibleEmployee:
        type: string
      OriginalEmployee:
        type: string
      Contacted:
        type: string
      Comment:
        type: string
      DoNotContact:
        type: string
      DoNotCall:
        type: string
      DoNotDirectMail:
        type: string
      DoNotEmail:
        type: string
      CustomerType:
        type: string
      FirstName:
        type: string
      LastName:
        type: string
      Company:
        type: string
  ArgsCustomerLeadStatusCheck:
    description: The request object for the Customer_Lead_Status_Check method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerLeadStatusCheck'
  CustomerLeadStatusCheck:
    description: Use to populate args body parameter for Customer_Lead_Status_Check method
    type: object
    required:
      - CustomerID
      - CustomerInterestID
    properties:
      CustomerID:
        type: string
      CustomerInterestID:
        type: string
  ArgsCustomerOrderAdd:
    description: The request object for the Customer_Order_Add method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerOrderAdd'
  CustomerOrderAdd:
    description: Use to populate args body parameter for Customer_Order_Add method
    type: object
    required:
      - Customer_ID
      - BillTo_CustomerLocationID
      - ShipTo_CustomerLocationID
      - DistributionMethodID
      - ExpectedOrderTotal
    properties:
      Customer_ID:
        type: string
      BillTo_CustomerLocationID:
        type: string
      ShipTo_CustomerLocationID:
        type: string
      DistributionMethodID:
        type: string
      ExpectedOrderTotal:
        type: string
      PONumber:
        type: string
      Order_Note:
        type: string
      Internal_Note:
        type: string
      ServiceRequest_Note:
        type: string
      LineItems:
        type: array
        items:
          $ref: '#/definitions/OrderLineItems'
  OrderLineItems:
    description: Use to build the LineItems property of the ArgsCustomerOrderAdd object in the Customer_Order_Add method
    type: object
    properties:
      ItemCode:
        type: string
      Quantity:
        type: number
        format: decimal
      Comment:
        type: string
  ArgsCustomerOrderLineItemCalculate:
    description: The request object for the Customer_Order_LineItem_Calculate method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerOrderLineItemCalculate'
  CustomerOrderLineItemCalculate:
    description: Use to populate args body parameter for Customer_Order_LineItem_Calculate method.
    type: object
    required:
      - ItemCode
      - Quantity
    properties:
      ItemCode:
        type: string
      Quantity:
        type: number
        format: decimal
      CustomerID:
        type: string
  CustomerOrderLineItemCalculates:
    description: Response to the Customer Order LineItem Calculate method. Tax and Totals specific to a  customer with promotional and discounts considered.
    type: object
    properties:
      Quantity:
        type: number
        format: decimal
      UnitPrice:
        type: number
        format: decimal
      Subtotal:
        type: number
        format: decimal
      Tax:
        type: number
        format: decimal
      Total:
        type: number
        format: decimal
  ArgsCustomerPaymentAdd:
    description: The request object for the Customer_Payment_Add method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerPaymentAdd'
  CustomerPaymentAdd:
    description: Use to populate args body parameter for Customer_Payment_Add method.
    type: object
    required:
      - Customer_ID
      - PaymentMethodID
      - Amount
    properties:
      Customer_ID:
        type: integer
      PaymentMethodID:
        type: integer
        description: A response value of the Payment_Search method
      Amount:
        type: number
        format: decimal
        description: Must be greater than zero
      NoteText:
        type: string
        description: ASCII text only. No rich text or binary files
      OrderID:
        type: string
        description: An Evosus Order ID created by the Order_Add method and return in the response value
  CustomerPaymentMethodSearch:
    description: Response to the Customer PaymentMethod Search method. Returns the PaymentMethodIDs used in the Customer Payment Add method.
    type: object
    properties:
      PaymentMethodID:
        type: integer
      PaymentCategory:
        type: string
      PaymentMethod:
        type: string
  ArgsCustomerStatementGet:
    description: The request object for the Customer_Statement_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerStatementGet'
  CustomerStatementGet:
    description: Use to populate args body parameter for Customer_Statement_Get method
    type: object
    required:
      - Customer_ID
      - Begin_Date
      - End_Date
    properties:
      Customer_ID:
        type: integer
      Begin_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      End_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  CustomerStatement:
    description: Customer Statement
    type: object
    properties:
      SortMe:
        type: string
      StoreName:
        type: string
      StoreAddress:
        type: string
      StoreWebSite:
        type: string
      StorePhone:
        type: string
      StoreFax:
        type: string
      StoreEmail:
        type: string
      Customer:
        type: string
      BalanceBegin:
        type: number
        format: decimal
      BalanceBeginDate:
        type: string
      BalanceEnd:
        type: number
        format: decimal
      BalanceEndDate:
        type: string
      BillTo:
        type: string
      Transaction:
        type: string
      Invoiced:
        type: string
      Comment:
        type: string
      Amount:
        type: string
      BalanceForward:
        type: number
        format: decimal
      LastPayment:
        type: string
      OrigAmount:
        type: number
        format: decimal
  ArgsCustomerScheduleSearch:
    description: The request object for the Customer_Schedule_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/CustomerScheduleSearch'
  CustomerScheduleSearch:
    description: Use to populate args body parameter for Customer_Schedule_Search method
    type: object
    required:
      - Customer_ID
      - Begin_Date
      - End_Date
    properties:
      Customer_ID:
        type: integer
      Begin_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      End_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  CustomerSchedule:
    description: Customer Schedule
    type: object
    properties:
      ScheduleID:
        type: integer
      Date:
        type: string
      Status:
        type: string
      Tech:
        type: string
      Task:
        type: string
      Hours:
        type: number
        format: decimal
      ShipTo_Contact:
        type: string
      ShipTo_Address:
        type: string
      ShipTo_City:
        type: string
      ShipTo_State:
        type: string
      ShipTo_PostCode:
        type: string
  ArgsEmployeeSearch:
    description: The request object for the Employee_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/EmployeeLogin'
  Employee:
    description: Employee
    type: object
    properties:
      FirstName:
        type: string
      LastName:
        type: string
      DisplayName:
        type: string
      LoginName:
        type: string
  EmployeeLogin:
    description: Employee
    type: object
    properties:
      LoginName:
        type: string        
  ArgsEmployeeTaskAdd:
    description: The request object for the Employee_Task_Add method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/EmployeeTaskAdd'
  EmployeeTaskAdd:
    description: Use to populate args body parameter for Employee_Task_Add method
    type: object
    required:
      - LoginName
      - NoteText
    properties:
      LoginName:
        type: string
      NoteText:
        type: string
      CustomerID:
        type: integer
  ArgsInventoryVendorSearch:
    description: The request object for the Inventory_Vendor_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/InventoryVendorSearch'
  InventoryVendorSearch:
    description: Use to populate args body parameter for Inventory_Vendor_Search method
    type: object
    properties:
      Vendor_ID:
        type: string
      Name:
        type: string
  Vendor:
    description: Vendor
    type: object
    properties:
      VendorID:
        type: string
      Name:
        type: string
      Address1:
        type: string
      Address2:
        type: string
      City:
        type: string
      State:
        type: string
      PostCode:
        type: string
      Country:
        type: string
      Phone:
        type: string
      Email:
        type: string
      Contact:
        type: string
      VendorType:
        type: string
      AustralianBusinessNumber:
        type: string
      UserDefined1:
        type: string
      UserDefined2:
        type: string
      UserDefined3:
        type: string
      EvosusInternalUse_SearchParmMatches:
        type: string
  ProductLine:
    description: Product Line
    type: object
    properties:
      ProductLineID:
        type: integer
      ProductLine:
        type: string
  DistributionMethod:
    description: Distribution Method
    type: object
    properties:
      DistributionMethodID:
        type: integer
      DistributionMethod:
        type: string
  ArgsInventoryItemGet:
    description: The request object for the Inventory_Item_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/InventoryItemGet'
  InventoryItemGet:
    description: Use to populate args body parameter for Inventory_Item_Get method
    type: object
    required:
      - ItemCode
    properties:
      ItemCode:
        type: string
      CustomerID:
        type: integer
  Item:
    description: Item
    type: object
    properties:
      Code:
        type: string
      Description:
        type: string
      ItemType:
        type: string
      Retail:
        type: number
        format: decimal
      SellBy:
        type: string
      SellByFactor:
        type: number
        format: decimal
      Cost:
        type: number
        format: decimal
      OrderBy:
        type: string
      OrderByFactor:
        type: number
        format: decimal
      UPC:
        type: string
      VendorID:
        type: string
      Vendor:
        type: string
      VendorItemCode:
        type: string
      VendorItemDescription:
        type: string
      EnforceSerialNo:
        type: string
      Make:
        type: string
      Model:
        type: string
      ProductLine:
        type: string
      Weight:
        type: string
      Volume:
        type: string
      ItemClass:
        type: string
      ItemSize:
        type: string
      Manufacturer:
        type: string
      BenefitsAndFeatures:
        type: string
      SalesMessage:
        type: string
      QuantityOnHand:
        type: number
        format: decimal
      QuantityReserved:
        type: number
        format: decimal
      QuantityAvailable:
        type: number
        format: decimal
      QuantityOnOrder:
        type: number
        format: decimal
      QuantityOnOrder_Text:
        type: string
      Discontinued:
        type: string
  ArgsInventoryItemStockSiteQuantityGet:
    description: The request object for the Inventory_Item_StockSite_Quantity_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/InventoryItemStockSiteQuantityGet'
  InventoryItemStockSiteQuantityGet:
    description: Use to populate args body parameter for Inventory_Item_StockSite_Quantity_Get method
    type: object
    required:
      - ItemCode
    properties:
      ItemCode:
        type: string
  ItemStockSite:
    description: ItemStockSite
    type: object
    properties:
      Code:
        type: string
      Description:
        type: string
      ProductLine:
        type: string
      ItemType:
        type: string
      Discontinued:
        type: string
      StockSite:
        type: string
      QuantityOnHand:
        type: number
        format: decimal
      QuantityReserved:
        type: number
        format: decimal
      QuantityAvailable:
        type: number
        format: decimal
      QuantityOnOrder:
        type: number
        format: decimal
      QuantityOnOrder_Text:
        type: string
      Location:
        type: string
  ArgsInventoryItemSearch:
    description: The request object for the Inventory_Item_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/InventoryItemSearch'
  InventoryItemSearch:
    description: Use to populate args body parameter for Inventory_Item_Search method
    type: object
    required:
      - ProductLineID
    properties:
      ProductLineID:
        type: string
      VendorID:
        type: string
      ItemCodeRange:
        type: string
      HasQuantityOnHand:
        type: string
  ArgsQuotesOpenSearch:
    description: The request object for the Quotes_Open_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/QuotesOpenSearch'
  QuotesOpenSearch:
    description: Use to populate args body parameter for Quotes_Open_Search method
    type: object
    properties:
      Customer_ID:
        type: integer
  ArgsQuotesClosedSearch:
    description: The request object for the Quotes_Closed_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/QuotesClosedSearch'
  QuotesClosedSearch:
    description: Use to populate args body parameter for Quotes_Closed_Search method
    type: object
    required:
     - args
    properties:
      Customer_ID:
        type: integer
      Begin_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      End_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  ArgsOrdersOpenSearch:
    description: The request object for the Orders_Open_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/OrdersOpenSearch'
  OrdersOpenSearch:
    description: Use to populate args body parameter for Orders_Open_Search method
    type: object
    properties:
      Customer_ID:
        type: integer
  ArgsOrdersClosedSearch:
    description: The request object for the Orders_Closed_Search method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/OrdersClosedSearch'
  OrdersClosedSearch:
    description: Use to populate args body parameter for Orders_Closed_Search method
    type: object
    required:
     - args
    properties:
      Customer_ID:
        type: integer
      Begin_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
      End_Date:
        type: string
        description: 'Format date as yyyy-MM-dd HH:mm:ss, ex: 2015-03-27 08:30:00'
  ArgsOrderQuoteItemsGet:
    description: The request object for the OrderQuote_Item_Get method
    type: object
    required:
      - args
    properties:
      args:
        $ref: '#/definitions/OrderQuoteItemGet'
  OrderQuoteItemGet:
    description: Use to populate args body parameter for OrderQuote_Items_Get method
    type: object
    properties:
      Order_ID:
        type: integer
        description: 'This is a generic input for QuoteID or OrderID.'