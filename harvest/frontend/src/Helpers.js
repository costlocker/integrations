import React from 'react';

const FullButton = ({ text, onClick }) => (
  <div className="bg-success text-center">
    <hr />
    <button className="btn btn-success btn-lg" onClick={onClick}>
      {text}
    </button>
    <hr />
  </div>
);

export { FullButton };
