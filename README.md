# 🌾 Farmflow - Smart Agricultural & Farm Management Platform

Farmflow is a modern, responsive web application for farmers to manage crop lifecycles, irrigation & fertilizer schedules, financial accounting, harvest logs, government scheme enrollments, and live Mandi market prices.

## 🚀 Running Directly in Chrome (No XAMPP Required!)

Farmflow has been converted to run **100% standalone** inside your web browser (Google Chrome, Edge, Safari, Firefox). **You do NOT need XAMPP, Apache, PHP, or MySQL installed.**

All farm data (Crops, Irrigation Tasks, Financial Ledger, Harvest Output, and Profile Settings) is automatically persisted in your browser's `localStorage`.

### 1. Quick Start (Development Mode)

```bash
# Install dependencies (if not already installed)
npm install

# Run the dev server
npm run dev
```

Open Chrome and navigate to: `http://localhost:5173`

### 2. Standalone Production Build

```bash
# Build production assets
npm run build

# Preview build
npm run preview
```

The compiled standalone app will be located in the `dist/` directory and can be served statically or opened directly in Chrome with zero backend server dependencies.

---

## 🛠️ Tech Stack & Features

- **Frontend Core**: React 19, Vite, TailwindCSS v4
- **Icons & Visuals**: Lucide React, Chart.js, React-ChartJS-2
- **Data Storage**: Client-side Browser LocalStorage Service (`apiService`)
- **Multilingual**: Multilingual support (English, Hindi, Marathi)
- **Features**:
  - 🌱 **Crop Management**: Lifecycle tracking, health metrics, and area yield estimates.
  - 💧 **Irrigation & Fertilizer Scheduler**: Water volume tracking, task scheduling, and advisories.
  - 💰 **Financial Log**: Automated income/expense ledger with category analytics.
  - 🚜 **Harvest Records**: Crop outputs, quality grading, Mandi sale rates, and warehouse logs.
  - 📜 **Govt Schemes**: Direct access to PM-KISAN, PMFBY, and solar pump subsidies.
  - 📊 **Live Mandi Prices & Weather**: Commodity price trends and 5-day advisories.
