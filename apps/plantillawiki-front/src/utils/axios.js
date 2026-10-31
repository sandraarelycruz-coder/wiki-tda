// src/services/api.js

import axios from "axios";
import { useAlertStore } from "../store/useAlertStore";
import { useLoadingStore } from "../store/useLoadingStore";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8006/api",
  timeout: 15000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

/**
 * Interceptor de solicitudes
 * Agrega el token JWT automáticamente
 */
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    config._globalLoaderTracked = true;
    useLoadingStore().startLoading();

    return config;
  },
  (error) => Promise.reject(error)
);

/**
 * Interceptor de respuestas
 */
api.interceptors.response.use(
  (response) => {
    if (response.config._globalLoaderTracked) {
      response.config._globalLoaderTracked = false;
      useLoadingStore().finishLoading();
    }

    return response;
  },

  (error) => {
    if (error.config?._globalLoaderTracked) {
      error.config._globalLoaderTracked = false;
      useLoadingStore().finishLoading();
    }

    const alertStore = useAlertStore();

    if (!error.response) {
      console.error("Error de conexión con el servidor.");
      alertStore.showAlert("Error de conexión con el servidor.", "error");
      return Promise.reject(error);
    }

    switch (error.response.status) {
      case 400:
        console.error("Solicitud incorrecta.");
        alertStore.showAlert("Solicitud incorrecta.", "error");
        break;

      case 401:
        console.error("Sesión expirada.");
        alertStore.showAlert("Sesión expirada. Por favor, inicia sesión nuevamente.", "warning");

        localStorage.removeItem("token");
        localStorage.removeItem("user");

        window.location.href = "/login";
        break;

      case 403:
        console.error("No tienes permisos.");
        alertStore.showAlert("No tienes permisos para realizar esta acción.", "error");
        break;

      case 404:
        console.error("Recurso no encontrado.");
        alertStore.showAlert("Recurso no encontrado.", "error");
        break;

      case 422:
        console.error("Error de validación.");
        alertStore.showAlert("Error de validación. Revisa los datos enviados.", "warning");
        break;

      case 500:
        console.error("Error interno del servidor.");
        alertStore.showAlert("Error interno del servidor.", "error");
        break;

      default:
        console.error("Ha ocurrido un error.");
        alertStore.showAlert("Ha ocurrido un error inesperado.", "error");
    }

    return Promise.reject(error);
  }
);

export default api;