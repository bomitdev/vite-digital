<template>
  <div class="container-fluid">
    <div class="row">
      <div>
        <div class="header-container">
          <img src="../components/img/logo-cnmh.png" alt="Image" class="logo" />
          <h1>โรงพยาบาลชานุมาน</h1>
        </div>
        <h2>
          วิสัยทัศน์ : โรงพยาบาลชุมชน คุณภาพชั้นนำ แห่งลุ่มน้ำโขง ในดวงใจประชาชน
        </h2>
        <hr />

        <!-- Dropdown เลือกเดือน -->
        <div class="month-selector">
          <label for="month"> </label>
          <select v-model="selectedMonth" @change="updateDays">
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
      schedule: [
        {
          name: "น.ส.ลัดดา จันนวล",
          duties: { 16: "IT", 18: "IT", 19: "IT", 20: "IT", 24: "IT", 30: "IT" },
        },
        {
          name: "นายศราวุฒิ แสนโท",
          duties: { 2: "IT", 7: "IT", 11: "IT", 12: "IT", 21: "IT" },
        },
        {
          name: "นายสุริยา จันทรา",
          duties: { 3: "IT", 15: "IT", 22: "IT", 25: "IT", 26: "IT", 27: "IT" },
        },
        {
          name: "นายธีระพงษ์ บุญหอม",
          duties: { 4: "IT", 5: "IT", 9: "IT", 14: "IT", 23: "IT", 28: "IT" },
        },
        {
          name: "นายยุทธชัย ภูมิลา",
          duties: { 1: "IT", 6: "IT",8: "IT", 10: "IT", 13: "IT", 17: "IT", 29: "IT" },
        },
      ],
      contacts: [
        { name: "นายศราวุฒิ แสนโท", phone: "086-8583828" },
        { name: "นางสาวลัดดา จันนวล", phone: "089-2869191" },
        { name: "นายธีรพงษ์ บุญหอม", phone: "087-3446570" },
        { name: "นายสุริยา จันทรา", phone: "097-3348861" },
        { name: "นายยุทธชัย ภูมิลา", phone: "091-2033764" },
      ],
    };
  },
  methods: {
    updateDays() {
      let year = new Date().getFullYear();
      let days = new Date(year, this.selectedMonth, 0).getDate();
      this.daysInMonth = Array.from({ length: days }, (_, i) => i + 1);
    },
  },
  mounted() {
    this.updateDays();
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
