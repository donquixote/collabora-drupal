/* -*- js-indent-level: 4 -*- */
/*
 * Copyright the Collabora Online contributors.
 *
 * SPDX-License-Identifier: MPL-2.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

function loadDocument(wopiClient, wopiSrc) {
    let wopiUrl = `${wopiClient}WOPISrc=${wopiSrc}`;
    let formElem = document.getElementById("collabora-submit-form");

    if (!formElem) {
        console.log('error: submit form not found');
        return;
    }
    formElem.action = wopiUrl;
    formElem.submit();
}
