// src/stores/dateStore.js
import { defineStore } from 'pinia';

export const useDateStore = defineStore('date', {
  state: () => {
    // กำหนดค่าเริ่มต้นเป็นปีงบประมาณเหมือนเดิม
    const today = new Date();
    let fiscalYear = today.getFullYear() + (today.getMonth() < 9 ? 0 : 1);

    return {
      startDate: `${fiscalYear - 1}-10-01`,
      endDate: `${fiscalYear}-09-30`,
      loading: false
    };
  },
  actions: {
    setDates(start, end) {
      this.startDate = start;
      this.endDate = end;
    }
  }
});
