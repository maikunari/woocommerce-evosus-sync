swagger: '2.0'
info:
  version: 6.7.XXX
  title: Evosus Web API
  description: "\nThis is the Evosus Web API for version 6.7.x and newer of Evosus Business Management.\n\nEvosus Web API is a licensed product of [Evosus, Inc.](http://www.evosus.com/)\n\nEach request requires at a minimum two pieces of information. The `CompanySN` and a `ticket`. The CompanySN corresponds to the unique serial number of your licensed installation of Evosus Business Management. The `ticket` value will be supplied by Evosus after your Web API account is created.\n\nIf you are not a licensed customer of Evosus, Inc. you may use the credentials for the demo account found below.\n### DEMO - Water World, Inc. Account\n\n```\n  CompanySN:  20101003175150*004\n  Ticket:     9ee46caf-7a8d-43e1-82bf-17932fa1bde1\n  \n```\n"
  termsOfService: 'http://evosus.com'
  contact:
    name: API Support
    url: 'http://support.evosus.com'
    email: support@evosus.com
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
  /method/TimecardPunches:
    post:
      tags:
        - Timecard
      description: Gets a list of timecard punch activity for a single employee for today.
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
      description: 'Creates a Timecards Punch for today. Either a punch in or punch out. '
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
  /method/Customer_Search:
    post:
      tags:
        - Customers
      description: "Returns basic information about a specific customer using Customer_ID, phone number, email address, name, address, and/or a legacy customer ID."
      summary: Search for a specific customer
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
              response: '[{"CustomerID": 123, "CompanyName": "John Doe", "LastName": "Smith", "FirstName": "Robert", "DisplayName": "Smith, Robert", BillTo_Contact": "Bob", "BillTo_Address1": "100 Main St.", "BillTo_Address2": "Apt. #1", "BillTo_City": "Portland", "BillTo_State": "OR", "BillTo_PostCode": "97203", "BillTo_Country": "US", "ShipTo_Contact": "Bob", "ShipTo_Address1": "100 Main St.", "ShipTo_Address2": "Apt #1", "ShipTo_City": "Portland", "ShipTo_State": "OR", "ShipTo_PostCode": "97203", "ShipTo_Country": "US", "Default_Phone": "(123) 123-1234", "Default_Email": "email@email.com", "Mobile_Phone": "(123) 123-1234", "BalanceDue": "$60,089.93", "FirstSaleDate": "11/16/2016", "LastSaleDate": "7/6/2017", "NumberOfSales": "11", "LifetimeSalesTotal": "280221.34", "FirstServiceDate": "11/17/2016", "LastServiceDate": "11/17/2016", "NumberOfService": "1", "LifetimeServiceTotal": "87.3", "CustomerType": "Residential", "DataConversion_LegacySystemID": "", "AustralianBusinessNumber": "", "EvosusInteralUse_SearchParmMatches": 1}]'
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
      description: Adds a note to a Customer/Lead.
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
      description: 'Returns a list of addresses associated with a specific customer using Customer_ID. For example, you can use this method to retrieve the bill to or ship to address of a customer using the Customer_ID.'
      summary: Gets addresses of a specific customer
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
              response: '[{"CustomerID": 123, "IsDefaultBillTo": True, ...}]'
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
  /method/Customer_Invoice_Search:
    post:
      tags:
        - Customers
      description: 'Produces a listing of invoices for a specific customer. '
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
              response: '[{"CustomerID": 123, "DocumentCategory": "Invoice", ...}]'
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
  /method/Customer_Lead_Search:
    post:
      tags:
        - Customers
      description: Produces a listing of active Leads in a date range.
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
  /method/Customer_Order_Add:
    post:
      tags:
        - Customers
      description: Add an Order to a customers profile. This interface supports a Sales order or a Service Work Order request.
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
  /method/Employee_Search:
    post:
      tags:
        - Employee
      description: Use to retrieve the data set of active employees. Employee Login name will be used as an input for some other web methods.
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
          required: true
  /method/Employee_Task_Add:
    post:
      tags:
        - Employee
      description: 'Use to send task/message to an employee or multiple employees. '
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
      description: Use to retrieve a data set of active Vendors that are associated with inventory items.
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
      description: Retrieve items base on specific filter.
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
      Username:
        type: string
      Password:
        type: string
      InPunchDate:
        type: string
        description: 'Format date/time as yyyy-MM-dd HH:mm:ss, ex: 2015-03-30 18:30:00'
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
      EmailAddress_List:
        type: string
      Name:
        type: string
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
      Lead_InterestName:
        type: string
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