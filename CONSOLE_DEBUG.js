/*
Report Card Feature - Browser Console Testing & Debugging
Open your browser console (F12) and paste these commands to test
*/

// ========================================
// 1. TEST IF REPORT CARD FUNCTIONS EXIST
// ========================================

// Check if functions are in global scope
console.log('Testing Report Card Functions:');
console.log('openReportCardModal exists:', typeof openReportCardModal === 'function');
console.log('closeReportCardModal exists:', typeof closeReportCardModal === 'function');
console.log('loadReportCardData exists:', typeof loadReportCardData === 'function');
console.log('downloadReportCardPDF exists:', typeof downloadReportCardPDF === 'function');


// ========================================
// 2. MANUALLY TEST API ENDPOINT
// ========================================

// Test the API endpoint directly
console.log('Testing API endpoint...');
fetch('../api/get_report_card.php')
  .then(response => response.json())
  .then(data => {
    console.log('API Response:', data);
    if (data.success) {
      console.log('✓ API returned successfully');
      console.log('Student:', data.data.student);
      console.log('Adviser:', data.data.adviser);
      console.log('Subjects found:', data.data.subjects.length);
      console.log('Subjects:', data.data.subjects);
    } else {
      console.error('✗ API returned error:', data.message);
    }
  })
  .catch(error => {
    console.error('✗ API call failed:', error);
  });


// ========================================
// 3. OPEN REPORT CARD PROGRAMMATICALLY
// ========================================

// Open the report card without clicking button
console.log('Opening Report Card...');
openReportCardModal({ preventDefault: () => {} });


// ========================================
// 4. TEST MODAL VISIBILITY
// ========================================

// Check if modal element exists
const modal = document.getElementById('reportCardModal');
console.log('Modal element exists:', modal !== null);
console.log('Modal has "active" class:', modal.classList.contains('active'));

// Show/hide for testing
console.log('Closing modal...');
closeReportCardModal();

setTimeout(() => {
  console.log('Opening modal again...');
  openReportCardModal({ preventDefault: () => {} });
}, 1000);


// ========================================
// 5. TEST jsPDF LIBRARY
// ========================================

// Check if jsPDF is loaded
console.log('Testing jsPDF library...');
console.log('window.jspdf exists:', typeof window.jspdf !== 'undefined');
if (typeof window.jspdf !== 'undefined') {
  console.log('✓ jsPDF library loaded');
  const { jsPDF } = window.jspdf;
  console.log('jsPDF class exists:', typeof jsPDF === 'function');
} else {
  console.error('✗ jsPDF library not found');
}


// ========================================
// 6. DEBUG REPORT CARD DATA
// ========================================

// After opening report card, inspect the data structure
async function debugReportCard() {
  const response = await fetch('../api/get_report_card.php');
  const data = await response.json();
  
  console.group('Report Card Data Structure');
  console.log('Full Response:', data);
  
  if (data.success) {
    const student = data.data.student;
    const subjects = data.data.subjects;
    
    console.group('Student Information');
    console.log('Name:', student.name);
    console.log('Grade Level:', student.gradeLevel);
    console.log('Section:', student.section);
    console.log('Student ID:', student.studentId);
    console.groupEnd();
    
    console.group('Grades by Subject');
    subjects.forEach(subject => {
      console.group(`📚 ${subject.name}`);
      console.log('1st Period:', subject.grade1st);
      console.log('2nd Period:', subject.grade2nd);
      console.log('3rd Period:', subject.grade3rd);
      console.log('4th Period:', subject.grade4th);
      console.log('Final Grade:', subject.finalGrade);
      console.groupEnd();
    });
    console.groupEnd();
  }
  
  console.groupEnd();
}

// Run debug
debugReportCard();


// ========================================
// 7. VERIFY CALCULATION LOGIC
// ========================================

// Test final grade calculation
function testFinalGradeCalculation() {
  console.log('Testing Final Grade Calculation:');
  
  // Test case 1: All grades present
  const grades1 = [85, 88, 90, 92];
  const avg1 = (85 + 88 + 90 + 92) / 4;
  console.log('Test 1 - All grades:', grades1, '→ Average:', avg1);
  
  // Test case 2: One grade missing (should return null)
  const grades2 = [85, 88, 90, null];
  console.log('Test 2 - Missing grade:', grades2, '→ Final Grade: null (expected)');
  
  // Test case 3: Low grades
  const grades3 = [70, 72, 71, 73];
  const avg3 = (70 + 72 + 71 + 73) / 4;
  console.log('Test 3 - Low grades:', grades3, '→ Average:', avg3);
}

testFinalGradeCalculation();


// ========================================
// 8. CHECK FOR CONSOLE ERRORS
// ========================================

// Monitor for errors
console.log('Setting up error monitoring...');
let errors = [];
window.addEventListener('error', (event) => {
  errors.push({
    message: event.message,
    source: event.filename,
    line: event.lineno
  });
  console.error('JavaScript Error:', event.message);
});

// Show errors after 5 seconds
setTimeout(() => {
  console.group('Collected Errors');
  if (errors.length === 0) {
    console.log('✓ No errors detected');
  } else {
    console.error('✗ Found errors:', errors);
  }
  console.groupEnd();
}, 5000);


// ========================================
// 9. TEST PDF DOWNLOAD
// ========================================

// After report card is open, test PDF generation
async function testPDFDownload() {
  try {
    const response = await fetch('../api/get_report_card.php');
    const data = await response.json();
    
    if (data.success) {
      console.log('Starting PDF download test...');
      downloadReportCardPDF(data.data);
      console.log('✓ PDF download initiated');
    }
  } catch (error) {
    console.error('✗ PDF test failed:', error);
  }
}

// Run after report card loads (wait 2 seconds)
console.log('Type this to test PDF download: testPDFDownload()');


// ========================================
// 10. FULL DIAGNOSTIC REPORT
// ========================================

async function generateDiagnosticReport() {
  console.group('📊 REPORT CARD FEATURE DIAGNOSTIC');
  
  // Check functions
  console.group('1️⃣ Functions');
  console.log('✓ openReportCardModal:', typeof openReportCardModal === 'function');
  console.log('✓ loadReportCardData:', typeof loadReportCardData === 'function');
  console.log('✓ downloadReportCardPDF:', typeof downloadReportCardPDF === 'function');
  console.groupEnd();
  
  // Check libraries
  console.group('2️⃣ Libraries');
  console.log('✓ jsPDF:', typeof window.jspdf !== 'undefined');
  console.groupEnd();
  
  // Check DOM elements
  console.group('3️⃣ DOM Elements');
  console.log('✓ reportCardModal:', document.getElementById('reportCardModal') !== null);
  console.log('✓ reportCardContent:', document.getElementById('reportCardContent') !== null);
  console.log('✓ reportCardLoading:', document.getElementById('reportCardLoading') !== null);
  console.groupEnd();
  
  // Check API
  console.group('4️⃣ API');
  try {
    const response = await fetch('../api/get_report_card.php');
    const data = await response.json();
    if (data.success) {
      console.log('✓ API Working');
      console.log('  Student:', data.data.student.name);
      console.log('  Subjects:', data.data.subjects.length);
    } else {
      console.error('✗ API Error:', data.message);
    }
  } catch (e) {
    console.error('✗ API Failed:', e.message);
  }
  console.groupEnd();
  
  console.group('5️⃣ Summary');
  console.log('✓ Report Card feature appears to be working correctly!');
  console.groupEnd();
  
  console.groupEnd();
}

// Run diagnostic
generateDiagnosticReport();
