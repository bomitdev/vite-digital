import ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';

/**
 * Format date string (YYYY-MM-DD) to Thai Buddhist date (DD ด.ม. YYYY)
 */
export function formatThaiDate(dateStr) {
  if (!dateStr || dateStr === '0000-00-00') return '-';
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  const year = parseInt(parts[0], 10) + 543;
  const monthNames = [
    'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
    'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
  ];
  const month = monthNames[parseInt(parts[1], 10) - 1] || parts[1];
  const day = parseInt(parts[2], 10);
  return `${day} ${month} ${year}`;
}

/**
 * Build asset description string from attributes
 */
export function buildDescription(row) {
  const parts = [];
  const title = (row.name || row.type || '').trim();
  if (title) parts.push(title);

  if (row.brand && !title.toLowerCase().includes(row.brand.toLowerCase())) {
    parts.push(`ยี่ห้อ ${row.brand}`);
  }

  if (row.model && !title.toLowerCase().includes(row.model.toLowerCase())) {
    parts.push(`รุ่น ${row.model}`);
  }

  if (row.size) {
    parts.push(`ขนาด ${row.size}`);
  }

  const specs = [];
  if (row.spec_cpu) specs.push(`CPU ${row.spec_cpu}`);
  if (row.spec_ram) specs.push(`RAM ${row.spec_ram}${!isNaN(row.spec_ram) ? 'GB' : ''}`);
  if (row.spec_storage) specs.push(`Storage ${row.spec_storage}${!isNaN(row.spec_storage) ? 'GB' : ''}`);
  if (row.os) specs.push(`OS ${row.os}`);

  if (specs.length > 0) {
    parts.push(`(${specs.join(', ')})`);
  }

  if (row.serial_number) {
    parts.push(`S/N: ${row.serial_number}`);
  }

  return parts.join(' ');
}

/**
 * Calculate standard Thai Government Depreciation (Straight-line 5 years / 20% per year, residual 1 Baht)
 */
export function calculateDepreciation(price, purchaseDateStr) {
  const numPrice = parseFloat(price) || 0;
  if (numPrice <= 1 || !purchaseDateStr || purchaseDateStr === '0000-00-00') {
    return {
      depreciation: 0,
      bookValue: numPrice
    };
  }

  const purchaseDate = new Date(purchaseDateStr);
  const reportDate = new Date();
  
  let months = (reportDate.getFullYear() - purchaseDate.getFullYear()) * 12 + (reportDate.getMonth() - purchaseDate.getMonth());
  if (reportDate.getDate() < purchaseDate.getDate()) {
    months -= 1;
  }
  months = Math.max(0, months);

  const usefulMonths = 60; // 5 years
  const maxDepreciation = numPrice - 1; // 1 Baht residual value

  if (months >= usefulMonths) {
    return {
      depreciation: Math.round(maxDepreciation * 100) / 100,
      bookValue: 1.00
    };
  } else {
    const monthlyRate = maxDepreciation / usefulMonths;
    const dep = Math.min(maxDepreciation, Math.round(monthlyRate * months * 100) / 100);
    return {
      depreciation: dep,
      bookValue: Math.round((numPrice - dep) * 100) / 100
    };
  }
}

/**
 * Export assets list to official provincial report Excel format
 * @param {Array} assetsList - list of asset records
 * @param {String} categoryTitle - category name for header (e.g. 'ครุภัณฑ์คอมพิวเตอร์')
 * @param {String} fileName - exported file name
 */
export async function exportProvinceAssetReport(
  assetsList = [],
  categoryTitle = 'ครุภัณฑ์คอมพิวเตอร์',
  fileName = 'รายงานทะเบียนครุภัณฑ์คอมพิวเตอร์_ส่งจังหวัด.xlsx'
) {
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'Vite-Digital Asset System';
  workbook.created = new Date();

  const sheet = workbook.addWorksheet('ทะเบียนครุภัณฑ์', {
    pageSetup: {
      paperSize: 9, // A4
      orientation: 'landscape',
      fitToPage: true,
      fitToWidth: 1,
      fitToHeight: 0,
      margins: {
        left: 0.4,
        right: 0.4,
        top: 0.6,
        bottom: 0.6,
        header: 0.3,
        footer: 0.3
      }
    },
    views: [{ showGridLines: true }]
  });

  const fontFamily = 'TH Sarabun New';
  const defaultFont = { name: fontFamily, size: 14, color: { argb: 'FF000000' } };
  const headerFont = { name: fontFamily, size: 14, bold: true, color: { argb: 'FF000000' } };
  const titleFont = { name: fontFamily, size: 16, bold: true, color: { argb: 'FF000000' } };

  const borderThin = {
    top: { style: 'thin', color: { argb: 'FF808080' } },
    left: { style: 'thin', color: { argb: 'FF808080' } },
    bottom: { style: 'thin', color: { argb: 'FF808080' } },
    right: { style: 'thin', color: { argb: 'FF808080' } }
  };

  const headerFill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FFD9D9D9' }
  };

  // Row 1: Title
  const row1 = sheet.getRow(1);
  row1.height = 30;
  const cellB1 = sheet.getCell('B1');
  cellB1.value = `ประเภท  ${categoryTitle}`;
  cellB1.font = titleFont;
  cellB1.alignment = { vertical: 'middle', horizontal: 'left' };

  // Set Row Heights for Headers
  const row2 = sheet.getRow(2);
  const row3 = sheet.getRow(3);
  row2.height = 26;
  row3.height = 24;

  // Header merges
  sheet.mergeCells('A2:A3');
  sheet.mergeCells('B2:B3');
  sheet.mergeCells('C2:C3');
  sheet.mergeCells('D2:D3');
  sheet.mergeCells('E2:E3');
  sheet.mergeCells('F2:F3');
  sheet.mergeCells('G2:G3');
  sheet.mergeCells('H2:H3');
  sheet.mergeCells('I2:I3');
  sheet.mergeCells('J2:J3');
  sheet.mergeCells('K2:K3');
  sheet.mergeCells('L2:L3');
  sheet.mergeCells('M2:N2');
  sheet.mergeCells('O2:P2');
  sheet.mergeCells('Q2:Q3');

  // Values for Row 2
  sheet.getCell('A2').value = 'ลำดับ';
  sheet.getCell('B2').value = 'วัน เดือน ปี';
  sheet.getCell('C2').value = 'เลขที่หรือรหัส';
  sheet.getCell('D2').value = 'รหัสสินทรัพย์ GF';
  sheet.getCell('E2').value = 'ยี่ห้อ ชนิด แบบ ขนาดและลักษณะ';
  sheet.getCell('F2').value = 'จำนวน\nหน่วย';
  sheet.getCell('G2').value = 'หน่วยนับ';
  sheet.getCell('H2').value = 'ราคาต่อหน่วย\n(บาท)';
  sheet.getCell('I2').value = 'ค่าเสื่อมราคา\nสะสม';
  sheet.getCell('J2').value = 'มูลค่า\nตามบัญชี';
  sheet.getCell('K2').value = 'วิธีการได้มา';
  sheet.getCell('L2').value = 'ใช้ประจำที่';
  sheet.getCell('M2').value = 'สถานะพัสดุ';
  sheet.getCell('O2').value = 'ตรวจ';
  sheet.getCell('Q2').value = 'หมายเหตุ';

  // Values for Row 3
  sheet.getCell('M3').value = 'ใช้ได้';
  sheet.getCell('N3').value = 'ชำรุด';
  sheet.getCell('O3').value = 'พบ';
  sheet.getCell('P3').value = 'ไม่พบ';

  const headerCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
  [2, 3].forEach(r => {
    headerCols.forEach(col => {
      const cell = sheet.getCell(`${col}${r}`);
      cell.font = headerFont;
      cell.fill = headerFill;
      cell.border = borderThin;
      cell.alignment = {
        vertical: 'middle',
        horizontal: 'center',
        wrapText: true
      };
    });
  });

  // Column Widths
  const colWidths = {
    A: 7,   // ลำดับ
    B: 14,  // วัน เดือน ปี
    C: 22,  // เลขที่หรือรหัส
    D: 16,  // รหัส GF
    E: 42,  // ยี่ห้อ ชนิด...
    F: 9,   // จำนวนหน่วย
    G: 9,   // หน่วยนับ
    H: 15,  // ราคาต่อหน่วย
    I: 15,  // ค่าเสื่อมสะสม
    J: 15,  // มูลค่าตามบัญชี
    K: 14,  // วิธีการได้มา
    L: 22,  // ใช้ประจำที่
    M: 8,   // ใช้ได้
    N: 8,   // ชำรุด
    O: 8,   // พบ
    P: 8,   // ไม่พบ
    Q: 20   // หมายเหตุ
  };
  Object.entries(colWidths).forEach(([col, width]) => {
    sheet.getColumn(col).width = width;
  });

  // Data rows
  let currentRow = 4;
  assetsList.forEach((item, index) => {
    const row = sheet.getRow(currentRow);
    row.height = 24;

    const price = parseFloat(item.price) || 0;
    const depData = calculateDepreciation(price, item.purchase_date);
    const unitText = item.unit ? item.unit.trim() : 'เครื่อง';

    const isUsable = item.status === 'Active' || item.status === 'Spare' || item.status === 'Borrowed';
    const isDamaged = item.status === 'Repair' || item.status === 'ชำรุด';

    sheet.getCell(`A${currentRow}`).value = index + 1;
    sheet.getCell(`A${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`B${currentRow}`).value = formatThaiDate(item.purchase_date);
    sheet.getCell(`B${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`C${currentRow}`).value = item.asset_code || '-';
    sheet.getCell(`C${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`D${currentRow}`).value = item.gf_code || '-';
    sheet.getCell(`D${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`E${currentRow}`).value = buildDescription(item);
    sheet.getCell(`E${currentRow}`).alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };

    sheet.getCell(`F${currentRow}`).value = 1;
    sheet.getCell(`F${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`G${currentRow}`).value = unitText;
    sheet.getCell(`G${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    const cellH = sheet.getCell(`H${currentRow}`);
    cellH.value = price;
    cellH.numFmt = '#,##0.00';
    cellH.alignment = { vertical: 'middle', horizontal: 'right' };

    const cellI = sheet.getCell(`I${currentRow}`);
    cellI.value = depData.depreciation;
    cellI.numFmt = '#,##0.00';
    cellI.alignment = { vertical: 'middle', horizontal: 'right' };

    const cellJ = sheet.getCell(`J${currentRow}`);
    cellJ.value = {
      formula: `H${currentRow}-I${currentRow}`,
      result: depData.bookValue
    };
    cellJ.numFmt = '#,##0.00';
    cellJ.alignment = { vertical: 'middle', horizontal: 'right' };

    sheet.getCell(`K${currentRow}`).value = item.acquisition_method || '-';
    sheet.getCell(`K${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`L${currentRow}`).value = item.location || '-';
    sheet.getCell(`L${currentRow}`).alignment = { vertical: 'middle', horizontal: 'left' };

    sheet.getCell(`M${currentRow}`).value = isUsable ? '/' : '';
    sheet.getCell(`M${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`N${currentRow}`).value = isDamaged ? '/' : '';
    sheet.getCell(`N${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`O${currentRow}`).value = '/';
    sheet.getCell(`O${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    sheet.getCell(`P${currentRow}`).value = '';
    sheet.getCell(`P${currentRow}`).alignment = { vertical: 'middle', horizontal: 'center' };

    const notesArr = [];
    if (item.notes) notesArr.push(item.notes.trim());
    if (item.responsible_person) notesArr.push(`ผู้รับผิดชอบ: ${item.responsible_person.trim()}`);
    sheet.getCell(`Q${currentRow}`).value = notesArr.join(' | ');
    sheet.getCell(`Q${currentRow}`).alignment = { vertical: 'middle', horizontal: 'left' };

    headerCols.forEach(col => {
      const c = sheet.getCell(`${col}${currentRow}`);
      c.font = defaultFont;
      c.border = borderThin;
    });

    currentRow++;
  });

  const lastDataRow = currentRow - 1;

  // Summary Row
  const totalRow = sheet.getRow(currentRow);
  totalRow.height = 28;
  sheet.mergeCells(`A${currentRow}:E${currentRow}`);
  const totalLabelCell = sheet.getCell(`A${currentRow}`);
  totalLabelCell.value = `รวมทั้งสิ้น (${assetsList.length} รายการ)`;
  totalLabelCell.font = headerFont;
  totalLabelCell.alignment = { vertical: 'middle', horizontal: 'center' };

  const totalF = sheet.getCell(`F${currentRow}`);
  totalF.value = { formula: `SUM(F4:F${lastDataRow})`, result: assetsList.length };
  totalF.font = headerFont;
  totalF.alignment = { vertical: 'middle', horizontal: 'center' };

  const totalG = sheet.getCell(`G${currentRow}`);
  totalG.value = 'เครื่อง';
  totalG.font = headerFont;
  totalG.alignment = { vertical: 'middle', horizontal: 'center' };

  const totalH = sheet.getCell(`H${currentRow}`);
  totalH.value = { formula: `SUM(H4:H${lastDataRow})` };
  totalH.font = headerFont;
  totalH.numFmt = '#,##0.00';
  totalH.alignment = { vertical: 'middle', horizontal: 'right' };

  const totalI = sheet.getCell(`I${currentRow}`);
  totalI.value = { formula: `SUM(I4:I${lastDataRow})` };
  totalI.font = headerFont;
  totalI.numFmt = '#,##0.00';
  totalI.alignment = { vertical: 'middle', horizontal: 'right' };

  const totalJ = sheet.getCell(`J${currentRow}`);
  totalJ.value = { formula: `SUM(J4:J${lastDataRow})` };
  totalJ.font = headerFont;
  totalJ.numFmt = '#,##0.00';
  totalJ.alignment = { vertical: 'middle', horizontal: 'right' };

  const totalFill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FFF2F2F2' }
  };
  const totalBorder = {
    top: { style: 'thin', color: { argb: 'FF808080' } },
    left: { style: 'thin', color: { argb: 'FF808080' } },
    bottom: { style: 'double', color: { argb: 'FF000000' } },
    right: { style: 'thin', color: { argb: 'FF808080' } }
  };

  headerCols.forEach(col => {
    const c = sheet.getCell(`${col}${currentRow}`);
    c.border = totalBorder;
    c.fill = totalFill;
    if (!c.font || !c.font.bold) {
      c.font = headerFont;
    }
  });

  // Committee Signature Block
  currentRow += 3;
  const signColStart = 'J';
  const signColEnd = 'P';

  const signBlocks = [
    '(ลงชื่อ)................................................................. ประธานกรรมการ',
    '(.................................................................)',
    'ตำแหน่ง..........................................................',
    '',
    '(ลงชื่อ)................................................................. กรรมการ',
    '(.................................................................)',
    'ตำแหน่ง..........................................................',
    '',
    '(ลงชื่อ)................................................................. กรรมการ',
    '(.................................................................)',
    'ตำแหน่ง..........................................................'
  ];

  signBlocks.forEach(text => {
    if (text) {
      sheet.mergeCells(`${signColStart}${currentRow}:${signColEnd}${currentRow}`);
      const sc = sheet.getCell(`${signColStart}${currentRow}`);
      sc.value = text;
      sc.font = { name: fontFamily, size: 14, color: { argb: 'FF000000' } };
      sc.alignment = { vertical: 'middle', horizontal: 'center' };
    }
    currentRow++;
  });

  // Generate buffer and trigger browser download
  const buffer = await workbook.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  });
  saveAs(blob, fileName);
}
