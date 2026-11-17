import axios from "axios";

const api = axios.create({
  baseURL: "/api",
  headers: {
    Accept: "application/json"
  }
});

export default () => {
  // Axios configuration
};

export { api };
