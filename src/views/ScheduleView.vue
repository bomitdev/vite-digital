<template>
  <div class="container-fluid">
    <div class="row">
      <div>
        <div class="header-container">
          <img src="../components/img/logo-cnmh.png" alt="Logo" class="logo" />
          <h1>โรงพยาบาลชานุมาน</h1>
        </div>
        <h2>
          วิสัยทัศน์ : โรงพยาบาลชุมชน คุณภาพชั้นนำ แห่งลุ่มน้ำโขง ในดวงใจประชาชน
        </h2>
        <hr />

        <!-- Dropdown เลือกเดือน -->
        <div class="month-selector">
          <label for="month">เลือกเดือน: </label>
          <select v-model="selectedMonth" @change="fetchSchedule">
            <option v-for="(month, index) in months" :key="index" :value="index + 1">
              {{ month }}
            </option>
          </select>
        </div>

        <h3>ตารางเวร IT : เดือน {{ months[selectedMonth - 1] }}</h3>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ชื่อ / วันที่</th>
              <th v-for="day in daysInMonth" :key="day">{{ day }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(person, index) in schedule" :key="index">
              <td class="name-cell">{{ person.name }}</td>
              <td v-for="day in daysInMonth" :key="day">
                {{ person.duties[day] || "" }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <br />
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ชื่อ</th>
              <th>เบอร์โทรติดต่อ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="contact in contacts" :key="contact.name">
              <td>{{ contact.name }}</td>
              <td>{{ contact.phone }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ScheduleView",
  data() {
    return {
      selectedMonth: new Date().getMonth() + 1, // ค่าเริ่มต้นเป็นเดือนปัจจุบัน
      daysInMonth: [],
      months: [
        "มกราคม",
        "กุมภาพันธ์",
        "มีนาคม",
        "เมษายน",
        "พฤษภาคม",
        "มิถุนายน",
        "กรกฎาคม",
        "สิงหาคม",
        "กันยายน",
        "ตุลาคม",
        "พฤศจิกายน",
        "ธันวาคม",
      ],
      schedule: [],
      contacts: [],
    };
  },
  methods: {
    async fetchSchedule() {
      try {
        let year = new Date().getFullYear();
        const response = await fetch(`http://192.168.7.12/vue-app/vite-digital/backend/api-digital/get-schedule.php?year=${year}&month=${this.selectedMonth}`);
        const data = await response.json();

        if (data && data.data) {
          this.schedule = data.data.map(person => ({
            name: person.name,
            duties: this.formatDuties(person),
          }));
          this.contacts = this.extractContacts(data.data);
          this.updateDays();
        } else {
          console.error("No data received");
        }
      } catch (error) {
        console.error("Error fetching data:", error);
      }
    },

    formatDuties(person) {
      let duties = {};
      for (let i = 1; i <= 31; i++) {
        duties[i] = person[`d${i}`] || "";
      }
      return duties;
    },

    extractContacts(data) {
      return data.map(person => ({
        name: person.name,
        phone: person.phone,
      }));
    },

    updateDays() {
      let year = new Date().getFullYear();
      let days = new Date(year, this.selectedMonth, 0).getDate();
      this.daysInMonth = Array.from({ length: days }, (_, i) => i + 1);
    },
  },
  mounted() {
    this.fetchSchedule();
  },
};
</script>

<style scoped>
body {
  background-color: #e0ffff;
  text-align: center;
}

h1,
h2,
h3 {
  font-weight: bold;
  color: purple;
}

h1 {
  font-size: 45px;
}

h2 {
  text-align: center;
  font-size: 35px;
  margin: 0;
}

h3 {
  font-size: 20px;
  text-align: left;
}

.logo {
  width: 90px;
  height: auto;
  margin-right: 10px;
}

.header-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.table-container {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 10px;
}

table {
  width: 100%;
  max-width: 800px;
  border-collapse: collapse;
  text-align: center;
}

th,
td {
  padding: 8px 12px;
  border: 1px solid black;
}

th {
  background-color: purple;
  color: white;
}

td {
  color: purple;
}

.name-cell {
  white-space: nowrap;
}

.month-selector {
  margin: 10px 0;
}
</style>
