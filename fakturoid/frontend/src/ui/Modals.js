import React from 'react';
import Modal from 'react-modal';
import { appState } from '../state';
import { Link, Button } from './Components';

const openModal = (type) => () => appState.cursor(['app', 'openedModals']).update(modals => modals.add(type));
const closeModal = (type) => () => appState.cursor(['app', 'openedModals']).update(modals => modals.delete(type));
const isOpened = (type) => appState.cursor(['app', 'openedModals']).deref().contains(type);

Modal.defaultStyles.overlay.backgroundColor = 'rgba(0, 0, 0, 0.5)';

const CenteredModal = ({ type, link, content, onOpen }) =>
  <div>
    <Link {...link } action={openModal(type)} />
    <Modal
      isOpen={isOpened(type)}
      onAfterOpen={onOpen}
      onRequestClose={closeModal(type)}
      className={{
        base: 'modal',
        afterOpen: 'modal fade in show',
        beforeClose: 'modal fade'
      }}
      bodyOpenClassName="modal-open"
      portalClassName={`modal-${type}`}
      contentLabel=""
      shouldCloseOnOverlayClick={true}
    >
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="text-right">
            <Button title={<span className="fa fa-times" />} action={closeModal(type)} className="btn btn-link" />
          </div>
          <div className="modal-body">
            {content(closeModal(type))}
          </div>
        </div>
      </div>
    </Modal>
  </div>;

export { CenteredModal };
