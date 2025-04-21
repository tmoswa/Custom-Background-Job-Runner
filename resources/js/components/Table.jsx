import React from 'react';
import PropTypes from 'prop-types';

const Table = ({ columns, data, renderRow }) => {
    return (
        <table className="table">
            <thead>
            <tr>
                {columns.map((col) => (
                    <th key={col.key} className="th">
                        {col.label}
                    </th>
                ))}
            </tr>
            </thead>
            <tbody>
            {data.map((item, index) => renderRow(item, index))}
            </tbody>
        </table>
    );
};

Table.propTypes = {
    columns: PropTypes.arrayOf(
        PropTypes.shape({
            key: PropTypes.string.isRequired,
            label: PropTypes.string.isRequired,
        })
    ).isRequired,
    data: PropTypes.array.isRequired,
    renderRow: PropTypes.func.isRequired,
};

export default Table;
