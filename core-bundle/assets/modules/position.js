import { arrow, computePosition, flip, offset, shift } from '@floating-ui/dom';

export function pointerAnchor(x, y) {
    return { getBoundingClientRect: () => new DOMRect(x, y, 0, 0) };
}

export function compute(anchor, element, arrowEl, placement = 'bottom') {
    const middleware = [offset(3), flip(), shift({ padding: 10 })];

    if (arrowEl) {
        middleware.push(arrow({ element: arrowEl }));
    }

    computePosition(anchor, element, { placement, middleware }).then(({ x, y, placement, middlewareData }) => {
        Object.assign(element.style, {
            left: `${x}px`,
            top: `${y}px`,
        });

        if (!arrowEl) {
            return;
        }

        const { x: arrowX, y: arrowY } = middlewareData.arrow;
        const [side, alignment] = placement.split('-');

        const staticSide = {
            top: 'bottom',
            right: 'left',
            bottom: 'top',
            left: 'right',
        }[side];

        const styles = {
            left: arrowX != null ? `${arrowX}px` : '',
            top: arrowY != null ? `${arrowY}px` : '',
            right: '',
            bottom: '',
            [staticSide]: '-4px',
        };

        const arrowAxis = { top: 'left', bottom: 'left', left: 'top', right: 'top' };

        if ('start' === alignment) {
            styles[arrowAxis[side]] = '16px';
        }

        Object.assign(arrowEl.style, styles);
    });
}
