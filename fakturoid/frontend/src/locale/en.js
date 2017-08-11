import React from 'react';

export default {
  // app (Router.js, Loading.js)
  page: {
    invoice: 'New Invoice',
    invoices: 'Invoices',
    invoicesHistory: 'Previously imported invoices',
    login: 'Login'
  },
  loading: {
    app: 'Loading Costlocker & Fakturoid integration',
    invoice: 'Loading fakturoid clients, Costlocker invoice',
    createInvoice: 'Creating invoice in Fakturoid',
  },
  notify: {
    reloadSubjects: 'Customers reloaded',
    unknownSubject: 'Select an existing customer in Fakturoid.',
    requiredFakturoid: 'Login in Fakturoid before creating invoicing',
  },
  // Login.js
  login: {
    error: 'Login error',
    email: 'Email address',
    slug: 'Fakturoid slug (subdomain)',
    token: 'API token',
    tokenHelp: 'Find your API token here',
    requiredCostlocker: 'At first you have to login to Costlocker',
    apiAggreement: 'I aggree with access to Fakturoid API',
    loginCostlocker: 'Login to Costlocker',
    loginFakturoid: 'Login to Fakturoid',
    switchAccount: 'Switch account',
  },
  // InvoiceList.js
  invoiceTypes: {
    '': 'All',
    'invoice': 'Standard',
    'proforma': {
      'full': 'Proforma (full)',
      'partial': 'Proforma (partial)'
    },
  },
  invoiceModal: {
    checkAll: ({ count }) => `Select all (${count})`,
    submit: 'Add selected',
  },
  invoiceLines: {
    people: 'People',
    peopleAndActivites: 'People and Activities',
    activities: "Activities",
    expenses: "Expenses",
    discount: 'Discount',
    other: 'Other',
    actions: {
      peopleAndActivites: 'Add people or activities',
      expenses: 'Add expenses',
      discount: 'Add discount',
      reset: 'Reset lines',
      newLine: 'Add a new line',
    },
    item: {
      quantity: 'Quantity',
      unit: 'Unit',
      name: 'Name',
      vat: 'VAT',
      unit_amount: 'Price per unit',
      total_amount: 'Total price',
      invalid: 'Line will be ignored and not imported to Fakturoid',
    },
    units: {
      quantity: 'ks',
      time: 'h',
    },
  },
  editor: {
    type: 'Invoice type',
    subject: 'Fakturoid client',
    createSubject: 'Add a new Fakturoid client',
    outdatedSubjects: 'Can\'t see a specific client?',
    reloadSubjects: 'Try refreshing the list',
    issuedAt: 'Issued at',
    due: 'Due',
    orderNumber: 'Order Number',
    noteBeforeLines: 'Note before invoice lines',
    note: {
      title: 'Note',
      placeholder: 'Add private note to invoice...',
    },
    advancedSettings: {
      up: 'Hide advanced settings',
      down: 'Show advanced settings',
    },
    revenueError: {
      title: ({ billedAmount }) => `Total amount must be smaller or equal to '${billedAmount}', otherwise the project revenue would be smaller than billing`,
      error: ({ linesAmount }) => `You cannot bill '${linesAmount}', bring down quantity or unit amount in lines.`,
    },
    submit: 'Create Invoice',
  },
  summary: {
    total: 'Total amount',
    totalWithVAT: 'Total amount (with VAT)',
    totalWithoutVAT: 'Total amount (without VAT)',
    vat: 'VAT',
  },
  invalidInvoice: {
    notDraft: {
      title: 'Invalid invoice state',
      error: 'Billing is already invoiced in Costlocker',
    },
    unknownBilling: {
      title: 'Unknown billing',
      error: 'Billing not found in Costlocker, or you aren\'t authorized to see the project',
    },
  },
  invoices: {
    client: 'Client',
    id: 'ID',
    issued: 'Issued',
    price: 'Price',
    priceVAT: 'Price with VAT',
    links: 'Links',
    linkProject: 'Open project',
    linkInvoice: 'Open invoice',
    noResults: 'No invoices found',
  },
  search: {
    query: 'Search',
  },
  // Invoice.js
  tutorial: {
    title: 'You are all set!',
    description: ({ costlockerButton }) => <span>You will now see button {costlockerButton} in Billing interface of a project</span>,
    linkCostlocker: 'Cool, take me back to Costlocker!',
  },
  createdInvoice: {
    title: 'Invoice successfuly created!',
    description: () => <span>The invoice was imported to Fakturoid.<br />You can now view the invoice in Fakturoid or go back to Costlocker</span>,
    linkFakturoid: 'Open in Fakturoid',
    linkSeparator: 'or',
    linkCostlocker: 'Go back to Costlocker',
  },
  // DisabledAddon.js
  disabledAddon: {
    title: 'Fakturoid integration is disabled',
    ask: 'Ask your owner to',
    enable: 'enable the addon in Settings',
    logout: ({ company }) => `Logout and switch company ${company}`,
  },
  // ErrorPage.js
  error: {
    title: 'Oops, something went wrong',
    description: ({ currentUrl }) => <span>
      Try <a href={currentUrl}>reload the page</a>. If the error is happening again, then sends us following information as text to
    </span>,
  },
};
