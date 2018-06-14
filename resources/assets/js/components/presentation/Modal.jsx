import React from 'react';
import PropTypes from 'prop-types';

class Modal extends React.Component
{
    constructor(props)
    {
        super(props);
    }

    render()
    {
        if (! this.props.isOpen) {
            return null;
        }

        return (
            <div className="fixed pin z-50 overflow-auto bg-smoke-light flex items-center justify-center">
                <div className="w-1/1  bg-white border-1 border-solid border-black">
                    <div className="bg-grey-lighter rounded-t px-4 py-2">
                        <h2>{this.props.headerText}</h2>
                    </div>
                    <div className="max-w-sm rounded overflow-hidden shadow-lg">
                        <div className="px-6 py-4">
                            <p className="text-grey-darker">
                                {this.props.bodyText}
                            </p>
                        </div>
                        <div className="px-6 py-4">
                            {this.props.successButton}
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

Modal.propTypes = {
    isOpen: PropTypes.bool.isRequired,
    headerText: PropTypes.string.isRequired,
    bodyText: PropTypes.string.isRequired,
    successButton: PropTypes.element.isRequired,
};

export default Modal;