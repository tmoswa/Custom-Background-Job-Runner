import React from 'react';
import PropTypes from 'prop-types';
import Button from './Button';

const Pagination = ({ currentPage, lastPage, onPageChange }) => {
    const pages = Array.from({ length: lastPage }, (_, i) => i + 1);

    return (
        <div className="flex justify-center gap-2 mt-4">
            <Button
                disabled={currentPage === 1}
                onClick={() => onPageChange(currentPage - 1)}
                className={currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}
            >
                Previous
            </Button>
            {pages.map((page) => (
                <Button
                    key={page}
                    onClick={() => onPageChange(page)}
                    className={page === currentPage ? 'bg-blue-800' : ''}
                >
                    {page}
                </Button>
            ))}
            <Button
                disabled={currentPage === lastPage}
                onClick={() => onPageChange(currentPage + 1)}
                className={currentPage === lastPage ? 'opacity-50 cursor-not-allowed' : ''}
            >
                Next
            </Button>
        </div>
    );
};

Pagination.propTypes = {
    currentPage: PropTypes.number.isRequired,
    lastPage: PropTypes.number.isRequired,
    onPageChange: PropTypes.func.isRequired,
};

export default Pagination;
