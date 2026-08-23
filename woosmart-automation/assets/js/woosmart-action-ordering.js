(function () {
    'use strict';

    function renumberActionRows(container) {
        var rows = container.querySelectorAll('.woosmart-action-row');

        rows.forEach(function (row, rowIndex) {
            row.setAttribute('data-index', rowIndex);

            var title = row.querySelector('strong');

            if (title) {
                title.textContent = 'عملیات ' + (rowIndex + 1);
            }

            row.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(
                    /actions\[\d+\]/,
                    'actions[' + rowIndex + ']'
                );
            });
        });
    }

    function getRows(container) {
        return Array.prototype.slice.call(
            container.querySelectorAll('.woosmart-action-row')
        );
    }

    function updateOrderButtons(container) {
        var rows = getRows(container);

        rows.forEach(function (row, index) {
            var controls = row.querySelector(
                '.woosmart-action-order-controls'
            );

            if (!controls) {
                return;
            }

            var upButton = controls.querySelector(
                '.woosmart-action-move-up'
            );

            var downButton = controls.querySelector(
                '.woosmart-action-move-down'
            );

            if (upButton) {
                upButton.disabled = index === 0;
                upButton.setAttribute(
                    'aria-disabled',
                    index === 0 ? 'true' : 'false'
                );
                upButton.title =
                    index === 0
                        ? 'این عملیات در ابتدای فهرست است.'
                        : 'انتقال عملیات به بالا';
            }

            if (downButton) {
                downButton.disabled = index === rows.length - 1;
                downButton.setAttribute(
                    'aria-disabled',
                    index === rows.length - 1 ? 'true' : 'false'
                );
                downButton.title =
                    index === rows.length - 1
                        ? 'این عملیات در انتهای فهرست است.'
                        : 'انتقال عملیات به پایین';
            }
        });
    }

    function moveRow(container, row, direction) {
        var sibling =
            direction === 'up'
                ? row.previousElementSibling
                : row.nextElementSibling;

        if (!sibling || !sibling.classList.contains('woosmart-action-row')) {
            return;
        }

        if (direction === 'up') {
            container.insertBefore(row, sibling);
        } else {
            container.insertBefore(row, sibling.nextElementSibling);
        }

        renumberActionRows(container);
        updateOrderButtons(container);
    }

    function addOrderControls(row, container) {
        if (
            row.querySelector('.woosmart-action-order-controls')
        ) {
            return;
        }

        var header = row.firstElementChild;

        if (!header) {
            return;
        }

        var controls = document.createElement('div');
        controls.className = 'woosmart-action-order-controls';
        controls.style.cssText =
            'display:flex;align-items:center;gap:6px;direction:rtl;margin-right:12px;';

        var upButton = document.createElement('button');
        upButton.type = 'button';
        upButton.className =
            'button woosmart-action-order-button woosmart-action-move-up';
        upButton.textContent = '↑ بالا';
        upButton.setAttribute('aria-label', 'انتقال عملیات به بالا');
        upButton.title = 'انتقال عملیات به بالا';

        var downButton = document.createElement('button');
        downButton.type = 'button';
        downButton.className =
            'button woosmart-action-order-button woosmart-action-move-down';
        downButton.textContent = '↓ پایین';
        downButton.setAttribute('aria-label', 'انتقال عملیات به پایین');
        downButton.title = 'انتقال عملیات به پایین';

        controls.appendChild(upButton);
        controls.appendChild(downButton);
        header.appendChild(controls);

        upButton.addEventListener('click', function () {
            moveRow(container, row, 'up');
        });

        downButton.addEventListener('click', function () {
            moveRow(container, row, 'down');
        });
    }

    function enhanceRows(container) {
        getRows(container).forEach(function (row) {
            addOrderControls(row, container);
        });

        updateOrderButtons(container);
    }

    function init() {
        var container = document.getElementById(
            'woosmart-actions-container'
        );

        if (!container) {
            return;
        }

        enhanceRows(container);

        var observer = new MutationObserver(function () {
            enhanceRows(container);
        });

        observer.observe(container, {
            childList: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
