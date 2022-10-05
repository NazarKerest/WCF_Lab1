define(["require", "exports", "tslib", "../Dom/Util"], function (require, exports, tslib_1, Util_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = exports.WoltlabCoreDialogElement = void 0;
    Util_1 = tslib_1.__importDefault(Util_1);
    const dialogContainer = document.createElement("div");
    class WoltlabCoreDialogElement extends HTMLElement {
        #content;
        #dialog;
        #form;
        #returnFocus;
        #title;
        constructor() {
            super();
            this.#content = document.createElement("div");
            this.#dialog = document.createElement("dialog");
            this.#title = document.createElement("div");
        }
        connectedCallback() {
            this.#attachDialog();
        }
        show(title) {
            if (title.trim().length === 0) {
                throw new Error("Cannot open the modal dialog without a title.");
            }
            this.#title.textContent = title;
            if (this.#dialog.parentElement === null) {
                if (dialogContainer.parentElement === null) {
                    document.getElementById("content").append(dialogContainer);
                }
                dialogContainer.append(this);
            }
            this.#dialog.showModal();
        }
        close() {
            this.#dialog.close();
            if (this.#returnFocus !== undefined) {
                const element = this.#returnFocus();
                element?.focus();
            }
            const event = new CustomEvent("afterClose");
            this.dispatchEvent(event);
        }
        get dialog() {
            return this.#dialog;
        }
        get content() {
            return this.#content;
        }
        set returnFocus(returnFocus) {
            if (typeof returnFocus !== "function") {
                throw new TypeError("Expected a callback function for the return focus.");
            }
            this.#returnFocus = returnFocus;
        }
        get open() {
            return this.#dialog.open;
        }
        attachFormControls(options) {
            if (this.#form !== undefined) {
                throw new Error("There is already a form control attached to this dialog.");
            }
            if (options.extra !== undefined && options.cancel === undefined) {
                options.cancel = "";
            }
            const formControl = document.createElement("woltlab-core-dialog-control");
            formControl.primary = options.primary;
            if (options.cancel !== undefined) {
                formControl.cancel = options.cancel;
            }
            if (options.extra !== undefined) {
                formControl.extra = options.extra;
            }
            this.#form = document.createElement("form");
            this.#form.method = "dialog";
            this.#form.classList.add("dialog__form");
            this.#content.insertAdjacentElement("beforebegin", this.#form);
            this.#form.append(this.#content, formControl);
            if (options.isAlert) {
                if (options.cancel === undefined) {
                    this.#dialog.setAttribute("role", "alert");
                }
                else {
                    this.#dialog.setAttribute("role", "alertdialog");
                }
            }
            this.#form.addEventListener("submit", (event) => {
                const evt = new CustomEvent("validate", { cancelable: true });
                this.dispatchEvent(evt);
                if (evt.defaultPrevented) {
                    event.preventDefault();
                }
            });
            this.#dialog.addEventListener("close", () => {
                if (this.#dialog.returnValue === "") {
                    // Dialog was not closed by submitting it.
                    return;
                }
                const evt = new CustomEvent("primary");
                this.dispatchEvent(evt);
            });
            formControl.addEventListener("cancel", () => {
                const event = new CustomEvent("cancel", { cancelable: true });
                this.dispatchEvent(event);
                if (!event.defaultPrevented) {
                    this.close();
                }
            });
            if (options.extra !== undefined) {
                formControl.addEventListener("extra", () => {
                    const event = new CustomEvent("extra");
                    this.dispatchEvent(event);
                });
            }
        }
        #attachDialog() {
            if (this.#dialog.parentElement !== null) {
                return;
            }
            let closeButton;
            const dialogRole = this.#dialog.getAttribute("role");
            if (dialogRole !== "alert" && dialogRole !== "alertdialog") {
                closeButton = document.createElement("button");
                closeButton.innerHTML = '<fa-icon size="24" name="xmark"></fa-icon>';
                closeButton.classList.add("dialog__closeButton");
                closeButton.addEventListener("click", () => {
                    this.close();
                });
            }
            const header = document.createElement("div");
            header.classList.add("dialog__header");
            this.#title.classList.add("dialog__title");
            header.append(this.#title);
            if (closeButton) {
                header.append(closeButton);
            }
            const doc = document.createElement("div");
            doc.classList.add("dialog__document");
            doc.setAttribute("role", "document");
            doc.append(header);
            this.#content.classList.add("dialog__content");
            if (this.#form) {
                doc.append(this.#form);
            }
            else {
                doc.append(this.#content);
            }
            this.#dialog.append(doc);
            this.#dialog.classList.add("dialog");
            this.#dialog.setAttribute("aria-labelledby", Util_1.default.identify(this.#title));
            this.#dialog.addEventListener("cancel", (event) => {
                if (!this.#shouldClose()) {
                    event.preventDefault();
                    return;
                }
            });
            // Close the dialog by clicking on the backdrop.
            //
            // Using the `close` event is not an option because it will
            // also trigger when holding the mouse button inside the
            // dialog and then releasing it on the backdrop.
            this.#dialog.addEventListener("mousedown", (event) => {
                if (event.target === this.#dialog) {
                    const event = new CustomEvent("backdrop", { cancelable: true });
                    this.dispatchEvent(event);
                    if (event.defaultPrevented) {
                        return;
                    }
                    if (this.#shouldClose()) {
                        this.close();
                    }
                }
            });
            this.append(this.#dialog);
        }
        #shouldClose() {
            const event = new CustomEvent("close");
            this.dispatchEvent(event);
            return event.defaultPrevented === false;
        }
        addEventListener(type, listener, options) {
            super.addEventListener(type, listener, options);
        }
    }
    exports.WoltlabCoreDialogElement = WoltlabCoreDialogElement;
    exports.default = WoltlabCoreDialogElement;
    function setup() {
        const name = "woltlab-core-dialog";
        if (window.customElements.get(name) === undefined) {
            window.customElements.define(name, WoltlabCoreDialogElement);
        }
    }
    exports.setup = setup;
});
