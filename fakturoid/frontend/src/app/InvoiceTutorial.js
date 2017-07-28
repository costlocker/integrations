import React from 'react';
import InvoicesList from './InvoicesList';
import { PageWithSubnav } from '../ui/App';
import { CostlockerLink, Image } from '../ui/Components';
import billing from '../images/billing.png';

export default function InvoiceTutorial({ invoices, subjects, renderContent }) {
  return <PageWithSubnav
    tabs={[
      {
        id: 'tutorial',
        name: 'New invoice',
        content: () => <div className="text-center">
          <div className="row">
            <div className="col-sm-12">
              <h1>You are all set!</h1>
              <br />
              You will now see button <strong className="btn btn-primary">Create invoice</strong> in Billing interface of a project.
            </div>
          </div>
          <div className="row">
            <div className="col-sm-12">
              <Image src={billing} className="img-responsive center-block" />
            </div>
          </div>
          <div className="row">
            <div className="col-sm-12">
              <CostlockerLink path="/dashboard/billing-outlook" title="Cool, take me back to Costlocker!" className="btn btn-success btn-lg" />
            </div>
          </div>
        </div>,
      },
      {
        id: 'invoices',
        name: 'Latest invoices',
        content: () => <InvoicesList invoices={invoices} subjects={subjects} />,
      },
    ]}
  />;
}
