/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2018 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

function copyToClipboard(str) {
    const el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    const selected = document.getSelection().rangeCount > 0 ? document.getSelection().getRangeAt(0) : false;
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    if (selected) {
        document.getSelection().removeAllRanges();
        document.getSelection().addRange(selected);
    }
};

document.addEventListener('DOMContentLoaded', function () {
    var $qrCode = $('#lightninghub__qrcode');
    var $expiry = $('#lightning_expiry');

    if ($qrCode.length > 0) {
        $qrCode.qrcode($qrCode.data('generate'));
    }
    if ($expiry.length > 0) {
        var date = new Date(parseInt($expiry.data('expiry'), 10) * 1000);
        $expiry.attr('datetime', date.toISOString()).text(date.toLocaleString());
    }
    $(document).on('click', '.lightninghub-order__copyToClipboard:not(".lightninghub-order__copyToClipboard--success")', (e) => {
        var $this = $(e.target);
        var copyText = $this.data().copy;
        if (!copyText) {
            return;
        }
        copyToClipboard(copyText);
        $this.addClass('lightninghub-order__copyToClipboard--success');
        setTimeout(function () {
            $this.removeClass('lightninghub-order__copyToClipboard--success');
        }, 3000);
    })
});