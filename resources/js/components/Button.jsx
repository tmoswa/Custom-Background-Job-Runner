import React from 'react';
import PropTypes from 'prop-types';

const Button = ({ as: Component = 'button', variant = 'primary', children, className, ...props }) => {
    const baseClasses = 'btn';
    const variantClasses = {
        primary: 'btn-primary',
        danger: 'btn-danger',
    }[variant] || 'btn-primary';

    return (
        <Component className={`${baseClasses} ${variantClasses} ${className || ''}`} {...props}>
            {children}
        </Component>
    );
};

Button.propTypes = {
    as: PropTypes.elementType,
    variant: PropTypes.oneOf(['primary', 'danger']),
    children: PropTypes.node.isRequired,
    className: PropTypes.string,
};

export default Button;
