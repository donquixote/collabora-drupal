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

function loadDocument(wopiClient, wopiSrc, options = null) {
  let hasCloseButton = false;
  let wopiUrl = `${wopiClient}WOPISrc=${wopiSrc}`;
  if (options && options.closebutton == true) {
    wopiUrl += '&closebutton=true';
    hasCloseButton = true;
  }

  window.addEventListener("message", receiveMessage.bind(null, hasCloseButton), false);

  let formElem = document.getElementById("collabora-submit-form");

  if (!formElem) {
    console.log("error: submit form not found");
    return;
  }
  formElem.action = wopiUrl;
  formElem.submit();
}

function postMessage(msg) {
  document.getElementById("collabora-online-viewer").contentWindow.postMessage(JSON.stringify(msg), '*');
}

function postReady() {
  postMessage({ MessageId: "Host_PostmessageReady" });
}

function receiveMessage(hasCloseButton, event) {
  let msg = JSON.parse(event.data);
  if (!msg) {
    return;
  }

  switch (msg.MessageId) {

    case "App_LoadingStatus":
      if (msg.Values && msg.Values.Status == "Document_Loaded") {
        postReady();
      }
      break;

    case "UI_Close":
      if (hasCloseButton) {
        if (msg.Values && msg.Values.EverModified) {
          let reply = { MessageId: "Action_Close" };
          postMessage(reply);
        }
        if (window.parent.location == window.location) {
          history.back();
        } else {
          /* we send back the UI_Close message to the parent frame. */
          window.parent.postMessage(event.data);
        }
      }
      break;
  }
}
