import { arrow, computePosition, flip, offset, shift } from '@floating-ui/dom';

export function compute(anchor, element, arrowEl) {
    computePosition(anchor, element, {
        placement: 'bottom',
        middleware: [offset(5), flip(), shift({ padding: 10 }), arrow({ element: arrowEl })],
    }).then(({ x, y, placement, middlewareData }) => {
        Object.assign(element.style, {
            left: `${x}px`,
            top: `${y}px`,
        });

        const {x: arrowX, y: arrowY} = middlewareData.arrow;

        const staticSide = {
            top: 'bottom',
            right: 'left',
            bottom: 'top',
            left: 'right',
        }[placement.split('-')[0]];

        Object.assign(arrowEl.style, {
            left: arrowX != null ? `${arrowX}px` : '',
            top: arrowY != null ? `${arrowY}px` : '',
            right: '',
            bottom: '',
            [staticSide]: '-4px',
        });
    });
}
