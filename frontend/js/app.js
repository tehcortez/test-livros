(function () {
    "use strict";

    const state = { livros: [], autores: [], assuntos: [] };
    const currency = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" });
    const feedback = document.getElementById("feedback");
    let feedbackTimer;

    document.getElementById("report-link").href = Api.relatorios.livrosPorAutor();

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function showMessage(message, type) {
        window.clearTimeout(feedbackTimer);
        feedback.textContent = message;
        feedback.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
        feedbackTimer = window.setTimeout(() => feedback.classList.add("d-none"), 4500);
    }

    function showView(name) {
        document.querySelectorAll(".view-section").forEach((section) => {
            section.classList.toggle("d-none", section.id !== `view-${name}`);
        });
        document.querySelectorAll("[data-view-link]").forEach((link) => {
            const active = link.dataset.viewLink === name;
            link.classList.toggle("active", active && link.classList.contains("nav-link"));
            if (link.matches(".nav-link")) link.setAttribute("aria-current", active ? "page" : "false");
        });
        const nav = document.getElementById("mainNav");
        if (nav.classList.contains("show") && window.bootstrap) {
            window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
        }
    }

    function updateCounts() {
        ["livros", "autores", "assuntos"].forEach((type) => {
            document.getElementById(`count-${type}`).textContent = state[type].length;
        });
    }

    function actionButton(label, action, id, style) {
        const button = element("button", `btn btn-sm ${style}`, label);
        button.type = "button";
        button.dataset.action = action;
        button.dataset.id = id;
        return button;
    }

    function actionsCell(record) {
        const cell = element("td", "text-end");
        const actions = element("div", "table-actions");
        actions.append(
            actionButton("Editar", "edit", record.id, "btn-outline-primary"),
            actionButton("Excluir", "delete", record.id, "btn-outline-danger")
        );
        cell.append(actions);
        return cell;
    }

    function setEmpty(type, isEmpty) {
        document.getElementById(`${type}-empty`).classList.toggle("d-none", !isEmpty);
    }

    function renderBooks() {
        const tbody = document.getElementById("livros-table");
        tbody.replaceChildren();
        state.livros.forEach((book) => {
            const row = element("tr");
            const titleCell = element("td");
            titleCell.append(
                element("div", "book-title", book.titulo),
                element("div", "book-subtitle", book.editora)
            );
            row.append(
                titleCell,
                element("td", "", book.anoPublicacao),
                element("td", "text-nowrap", currency.format(Number(book.valor))),
                actionsCell(book)
            );
            tbody.append(row);
        });
        setEmpty("livros", state.livros.length === 0);
    }

    function renderSimpleList(type) {
        const tbody = document.getElementById(`${type}-table`);
        tbody.replaceChildren();
        state[type].forEach((record) => {
            const row = element("tr");
            const name = type === "autores" ? record.nome : record.descricao;
            row.append(element("td", "", name), actionsCell(record));
            tbody.append(row);
        });
        setEmpty(type, state[type].length === 0);
    }

    function fillChoices(type, records, selectedIds) {
        const container = document.getElementById(`livro-${type}`);
        container.replaceChildren();
        if (records.length === 0) {
            container.append(element("span", "text-secondary small", `Cadastre ${type} antes de associá-los.`));
            return;
        }
        const field = type === "autores" ? "nome" : "descricao";
        records.forEach((record) => {
            const label = element("label", "choice-item");
            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.value = record.id;
            checkbox.checked = selectedIds.includes(Number(record.id));
            label.append(checkbox, element("span", "", record[field]));
            container.append(label);
        });
    }

    function refreshBookChoices(selectedAuthors, selectedSubjects) {
        fillChoices("autores", state.autores, selectedAuthors || []);
        fillChoices("assuntos", state.assuntos, selectedSubjects || []);
    }

    async function loadAll() {
        const [livros, autores, assuntos] = await Promise.all([
            Api.livros.list(),
            Api.autores.list(),
            Api.assuntos.list()
        ]);
        state.livros = livros;
        state.autores = autores;
        state.assuntos = assuntos;
        renderBooks();
        renderSimpleList("autores");
        renderSimpleList("assuntos");
        refreshBookChoices();
        updateCounts();
    }

    function selectedIds(containerId) {
        return [...document.querySelectorAll(`#${containerId} input:checked`)]
            .map((input) => Number(input.value));
    }

    function formName(type) {
        return {
            livros: "livro",
            autores: "autor",
            assuntos: "assunto"
        }[type];
    }

    function resetForm(type) {
        const name = formName(type);
        const form = document.getElementById(`${name}-form`);
        form.reset();
        document.getElementById(`${name}-id`).value = "";
        document.getElementById(`${name}-form-title`).textContent = `Novo ${name}`;
        document.getElementById(`${name}-submit`).textContent = `Salvar ${name}`;
        document.getElementById(`${name}-cancel`).classList.add("d-none");
        if (type === "livros") refreshBookChoices();
    }

    function startEdit(type, id) {
        const name = formName(type);
        const record = state[type].find((item) => Number(item.id) === Number(id));
        if (!record) return;
        document.getElementById(`${name}-id`).value = record.id;
        document.getElementById(`${name}-form-title`).textContent = `Editar ${name}`;
        document.getElementById(`${name}-submit`).textContent = "Atualizar";
        document.getElementById(`${name}-cancel`).classList.remove("d-none");

        if (type === "livros") {
            document.getElementById("livro-titulo").value = record.titulo;
            document.getElementById("livro-editora").value = record.editora;
            document.getElementById("livro-edicao").value = record.edicao;
            document.getElementById("livro-ano").value = record.anoPublicacao;
            document.getElementById("livro-valor").value = record.valor;
            refreshBookChoices(record.autores || [], record.assuntos || []);
        } else if (type === "autores") {
            document.getElementById("autor-nome").value = record.nome;
        } else {
            document.getElementById("assunto-descricao").value = record.descricao;
        }

        document.getElementById(`${name}-form`).scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function bindForm(type) {
        const name = formName(type);
        document.getElementById(`${name}-form`).addEventListener("submit", async (event) => {
            event.preventDefault();
            const id = document.getElementById(`${name}-id`).value;
            let data;

            if (type === "livros") {
                data = {
                    titulo: document.getElementById("livro-titulo").value.trim(),
                    editora: document.getElementById("livro-editora").value.trim(),
                    edicao: Number(document.getElementById("livro-edicao").value),
                    anoPublicacao: Number(document.getElementById("livro-ano").value),
                    valor: document.getElementById("livro-valor").value,
                    autores: selectedIds("livro-autores"),
                    assuntos: selectedIds("livro-assuntos")
                };
            } else if (type === "autores") {
                data = { nome: document.getElementById("autor-nome").value.trim() };
            } else {
                data = { descricao: document.getElementById("assunto-descricao").value.trim() };
            }

            const submit = document.getElementById(`${name}-submit`);
            submit.disabled = true;
            try {
                const api = Api[type];
                if (id) await api.update(id, data);
                else await api.create(data);
                resetForm(type);
                await loadAll();
                showMessage(id ? "Cadastro atualizado." : "Cadastro criado.", "success");
            } catch (error) {
                showMessage(error.message, "danger");
            } finally {
                submit.disabled = false;
            }
        });

        document.getElementById(`${name}-cancel`).addEventListener("click", () => resetForm(type));
    }

    function bindTable(type) {
        document.getElementById(`${type}-table`).addEventListener("click", async (event) => {
            const button = event.target.closest("button[data-action]");
            if (!button) return;
            const { action, id } = button.dataset;

            if (action === "edit") {
                startEdit(type, id);
                return;
            }

            const record = state[type].find((item) => Number(item.id) === Number(id));
            const label = type === "livros" ? record.titulo : type === "autores" ? record.nome : record.descricao;
            if (!window.confirm(`Excluir "${label}"?`)) return;

            try {
                await Api[type].remove(id);
                await loadAll();
                showMessage("Cadastro excluído.", "success");
            } catch (error) {
                showMessage(error.message, "danger");
            }
        });
    }

    document.querySelectorAll("[data-view-link]").forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            showView(link.dataset.viewLink);
        });
    });

    ["livros", "autores", "assuntos"].forEach((type) => {
        bindForm(type);
        bindTable(type);
    });

    loadAll().catch((error) => showMessage(error.message, "danger"));
}());
