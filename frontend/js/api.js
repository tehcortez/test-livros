(function () {
    "use strict";

    const baseUrl = (window.API_BASE_URL || "http://localhost:8000/api").replace(/\/+$/, "");

    async function request(path, options) {
        let response;

        try {
            response = await fetch(`${baseUrl}${path}`, {
                ...options,
                headers: {
                    Accept: "application/json",
                    ...(options && options.body ? { "Content-Type": "application/json" } : {}),
                    ...(options ? options.headers : {})
                }
            });
        } catch (error) {
            throw new Error("Não foi possível conectar à API. Verifique se o backend está em execução.");
        }

        if (response.status === 204) {
            return null;
        }

        const text = await response.text();
        let data = null;

        if (text) {
            try {
                data = JSON.parse(text);
            } catch (error) {
                throw new Error("A API retornou uma resposta inválida.");
            }
        }

        if (!response.ok) {
            const detail = Array.isArray(data && data.detalhes) ? data.detalhes.join(" ") : "";
            throw new Error(detail || (data && data.erro) || `Erro na requisição (${response.status}).`);
        }

        return data;
    }

    function jsonOptions(method, body) {
        return { method, body: JSON.stringify(body) };
    }

    window.Api = {
        livros: {
            list: () => request("/livros"),
            create: (data) => request("/livros", jsonOptions("POST", data)),
            update: (id, data) => request(`/livros/${id}`, jsonOptions("PUT", data)),
            remove: (id) => request(`/livros/${id}`, { method: "DELETE" })
        },
        autores: {
            list: () => request("/autores"),
            create: (data) => request("/autores", jsonOptions("POST", data)),
            update: (id, data) => request(`/autores/${id}`, jsonOptions("PUT", data)),
            remove: (id) => request(`/autores/${id}`, { method: "DELETE" })
        },
        assuntos: {
            list: () => request("/assuntos"),
            create: (data) => request("/assuntos", jsonOptions("POST", data)),
            update: (id, data) => request(`/assuntos/${id}`, jsonOptions("PUT", data)),
            remove: (id) => request(`/assuntos/${id}`, { method: "DELETE" })
        },
        relatorios: {
            livrosPorAutor: () => `${baseUrl}/relatorios/livros-por-autor`
        }
    };
}());
