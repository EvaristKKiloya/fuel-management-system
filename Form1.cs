using System;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Windows.Forms;

namespace CalculatorForm
{
    public partial class Form1 : Form
    {
        private Label display;
        private Label historyLabel;
        private double storedValue;
        private string currentOperation = "";
        private bool isNewEntry = true;
        private List<string> calculationHistory = new List<string>();
        private string historyFilePath;
        private Color normalBackColor = Color.FromArgb(32, 32, 32);
        private Color errorBackColor = Color.FromArgb(180, 0, 0);
        private TableLayoutPanel calculatorPanel;
        private Panel displayPanel;
        private Panel currencyPanel;
        private Panel agePanel;

        public Form1()
        {
            // IMPORTANT: remove designer layout influence
            InitializeComponent();
            Controls.Clear();

            // Set history file path
            historyFilePath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments), "CalculatorHistory.txt");
            LoadHistory();

            // Form settings
            Text = "Calculator";
            FormBorderStyle = FormBorderStyle.FixedSingle;
            MaximizeBox = false;
            BackColor = Color.FromArgb(32, 32, 32);
            ClientSize = new Size(320, 540);
            KeyPreview = true;
            KeyDown += Form1_KeyDown;

            // ===== MENU =====
            MenuStrip menuStrip = new MenuStrip
            {
                BackColor = Color.FromArgb(24, 24, 24),
                ForeColor = Color.White
            };

            ToolStripMenuItem historyMenu = new ToolStripMenuItem("History");
            historyMenu.DropDownItems.Add("View History", null, ViewHistory_Click);
            historyMenu.DropDownItems.Add("Clear History", null, ClearHistory_Click);
            menuStrip.Items.Add(historyMenu);

            ToolStripMenuItem toolsMenu = new ToolStripMenuItem("Tools");
            toolsMenu.DropDownItems.Add("Calculate Age", null, CalculateAge_Click);
            toolsMenu.DropDownItems.Add("Currency Convert", null, CurrencyConvert_Click);
            menuStrip.Items.Add(toolsMenu);

            // ===== DISPLAY PANEL =====
            displayPanel = new Panel
            {
                Dock = DockStyle.Top,
                Height = 120,
                BackColor = Color.FromArgb(24, 24, 24),
                Padding = new Padding(10)
            };

            historyLabel = new Label
            {
                Text = "",
                Dock = DockStyle.Top,
                ForeColor = Color.Gray,
                Font = new Font("Segoe UI", 12),
                TextAlign = ContentAlignment.BottomRight,
                Height = 30
            };

            display = new Label
            {
                Text = "0",
                Dock = DockStyle.Fill,
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 36, FontStyle.Bold),
                TextAlign = ContentAlignment.BottomRight
            };

            displayPanel.Controls.Add(display);
            displayPanel.Controls.Add(historyLabel);

            // ===== BUTTON PANEL =====
            calculatorPanel = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                RowCount = 6,
                ColumnCount = 4,
                BackColor = Color.FromArgb(40, 40, 40),
                Padding = new Padding(8)
            };

            for (int i = 0; i < 4; i++)
                calculatorPanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 25F));

            for (int i = 0; i < 6; i++)
                calculatorPanel.RowStyles.Add(new RowStyle(SizeType.Percent, 16.66F));

            string[,] buttons =
            {
                { "%", "CE", "C", "⌫" },
                { "1/x", "x²", "√x", "÷" },
                { "7", "8", "9", "×" },
                { "4", "5", "6", "−" },
                { "1", "2", "3", "+" },
                { "±", "0", ".", "=" }
            };

            for (int r = 0; r < 6; r++)
            {
                for (int c = 0; c < 4; c++)
                {
                    Button btn = new Button
                    {
                        Text = buttons[r, c],
                        Dock = DockStyle.Fill,
                        FlatStyle = FlatStyle.Flat,
                        Font = new Font("Segoe UI", 14),
                        Margin = new Padding(4),
                        ForeColor = Color.WhiteSmoke,
                        BackColor = Color.FromArgb(60, 60, 60),
                        Tag = buttons[r, c]
                    };

                    btn.FlatAppearance.BorderSize = 0;
                    btn.Click += Button_Click;

                    if (btn.Text == "=")
                        btn.BackColor = Color.FromArgb(0, 120, 215);

                    calculatorPanel.Controls.Add(btn, c, r);
                }
            }

            // ===== ADD CONTROLS IN CORRECT ORDER =====
            Controls.Add(calculatorPanel);
            Controls.Add(displayPanel);
            Controls.Add(menuStrip);
        }

        private void Button_Click(object? sender, EventArgs e)
        {
            Button btn = (Button)sender!;
            string btnText = btn.Tag?.ToString() ?? "";

            // Number buttons
            if (double.TryParse(btnText, out _))
            {
                BackColor = normalBackColor; // Reset color on new input
                if (isNewEntry)
                {
                    display.Text = btnText;
                    isNewEntry = false;
                }
                else
                {
                    if (display.Text == "0")
                        display.Text = btnText;
                    else
                        display.Text += btnText;
                }
            }
            // Decimal point
            else if (btnText == ".")
            {
                if (isNewEntry)
                {
                    display.Text = "0.";
                    isNewEntry = false;
                }
                else if (!display.Text.Contains("."))
                {
                    display.Text += ".";
                }
            }
            // Clear
            else if (btnText == "C")
            {
                BackColor = normalBackColor; // Reset color on clear
                display.Text = "0";
                storedValue = 0;
                currentOperation = "";
                isNewEntry = true;
                historyLabel.Text = "";
            }
            // Clear Entry
            else if (btnText == "CE")
            {
                display.Text = "0";
                isNewEntry = true;
            }
            // Backspace
            else if (btnText == "⌫")
            {
                if (display.Text.Length > 1)
                    display.Text = display.Text.Substring(0, display.Text.Length - 1);
                else
                    display.Text = "0";
            }
            // Plus/Minus
            else if (btnText == "±")
            {
                if (double.TryParse(display.Text, out double val))
                {
                    display.Text = (-val).ToString();
                }
            }
            // Square
            else if (btnText == "x²")
            {
                if (double.TryParse(display.Text, out double val))
                {
                    double result = val * val;
                    string calculation = $"sqr({val}) = {result}";
                    AddToHistory(calculation);
                    display.Text = result.ToString();
                    isNewEntry = true;
                }
            }
            // Square Root
            else if (btnText == "√x")
            {
                if (double.TryParse(display.Text, out double val))
                {
                    if (val >= 0)
                    {
                        double result = Math.Sqrt(val);
                        string calculation = $"√({val}) = {result}";
                        AddToHistory(calculation);
                        display.Text = result.ToString();
                        isNewEntry = true;
                    }
                    else
                    {
                        MessageBox.Show("Cannot calculate square root of negative number", "Error");
                    }
                }
            }
            // Reciprocal
            else if (btnText == "1/x")
            {
                if (double.TryParse(display.Text, out double val))
                {
                    if (val != 0)
                    {
                        double result = 1 / val;
                        string calculation = $"1/({val}) = {result}";
                        AddToHistory(calculation);
                        display.Text = result.ToString();
                        isNewEntry = true;
                    }
                    else
                    {
                        BackColor = errorBackColor;
                        MessageBox.Show("Cannot divide by zero", "Error");
                    }
                }
            }
            // Percentage
            else if (btnText == "%")
            {
                if (double.TryParse(display.Text, out double val))
                {
                    double result = storedValue * (val / 100);
                    display.Text = result.ToString();
                    isNewEntry = true;
                }
            }
            // Equals
            else if (btnText == "=")
            {
                if (!string.IsNullOrEmpty(currentOperation))
                {
                    if (double.TryParse(display.Text, out double val))
                    {
                        double result = PerformCalculation(storedValue, val, currentOperation);
                        string calculation = $"{storedValue} {GetOperationSymbol(currentOperation)} {val} = {result}";
                        AddToHistory(calculation);
                        display.Text = result.ToString();
                        currentOperation = "";
                        isNewEntry = true;
                        historyLabel.Text = "";
                    }
                }
            }
            // Operations: +, −, ×, ÷
            else if (btnText == "+" || btnText == "−" || btnText == "×" || btnText == "÷")
            {
                if (double.TryParse(display.Text, out double val))
                {
                    if (!string.IsNullOrEmpty(currentOperation))
                    {
                        double result = PerformCalculation(storedValue, val, currentOperation);
                        display.Text = result.ToString();
                        storedValue = result;
                    }
                    else
                    {
                        storedValue = val;
                    }
                    
                    currentOperation = btnText;
                    historyLabel.Text = $"{storedValue} {btnText}";
                    isNewEntry = true;
                }
            }
        }

        private double PerformCalculation(double num1, double num2, string operation)
        {
            switch (operation)
            {
                case "+":
                    return num1 + num2;
                case "−":
                    return num1 - num2;
                case "×":
                    return num1 * num2;
                case "÷":
                    if (num2 != 0)
                        return num1 / num2;
                    else
                    {
                        BackColor = errorBackColor;
                        MessageBox.Show("Cannot divide by zero", "Error");
                        return 0;
                    }
                default:
                    return num2;
            }
        }

        private string GetOperationSymbol(string operation)
        {
            return operation;
        }

        private void AddToHistory(string calculation)
        {
            string entry = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] {calculation}";
            calculationHistory.Add(entry);
            SaveHistory();
        }

        private void SaveHistory()
        {
            try
            {
                File.WriteAllLines(historyFilePath, calculationHistory);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error saving history: {ex.Message}", "Error");
            }
        }

        private void LoadHistory()
        {
            try
            {
                if (File.Exists(historyFilePath))
                {
                    calculationHistory = new List<string>(File.ReadAllLines(historyFilePath));
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error loading history: {ex.Message}", "Error");
            }
        }

        private void ViewHistory_Click(object? sender, EventArgs e)
        {
            Form historyForm = new Form
            {
                Text = "Calculation History",
                Size = new Size(500, 400),
                BackColor = Color.FromArgb(32, 32, 32),
                StartPosition = FormStartPosition.CenterParent
            };

            ListBox historyList = new ListBox
            {
                Dock = DockStyle.Fill,
                BackColor = Color.FromArgb(24, 24, 24),
                ForeColor = Color.White,
                Font = new Font("Consolas", 10),
                BorderStyle = BorderStyle.None
            };

            if (calculationHistory.Count > 0)
            {
                for (int i = calculationHistory.Count - 1; i >= 0; i--)
                {
                    historyList.Items.Add(calculationHistory[i]);
                }
            }
            else
            {
                historyList.Items.Add("No history available");
            }

            historyForm.Controls.Add(historyList);
            historyForm.ShowDialog();
        }

        private void ClearHistory_Click(object? sender, EventArgs e)
        {
            DialogResult result = MessageBox.Show("Are you sure you want to clear all history?", 
                "Clear History", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            
            if (result == DialogResult.Yes)
            {
                calculationHistory.Clear();
                SaveHistory();
                MessageBox.Show("History cleared successfully", "Success");
            }
        }

        private void CalculateAge_Click(object? sender, EventArgs e)
        {
            // Hide calculator
            calculatorPanel.Visible = false;
            displayPanel.Visible = false;

            // Create age panel if it doesn't exist
            if (agePanel == null)
            {
                agePanel = new Panel
                {
                    Dock = DockStyle.Fill,
                    BackColor = Color.FromArgb(32, 32, 32)
                };

                Label lblTitle = new Label
                {
                    Text = "Age Calculator",
                    Location = new Point(20, 20),
                    Size = new Size(280, 30),
                    ForeColor = Color.White,
                    Font = new Font("Segoe UI", 16, FontStyle.Bold)
                };

                Label lblBirthDate = new Label
                {
                    Text = "Enter Birth Date:",
                    Location = new Point(20, 70),
                    Size = new Size(120, 25),
                    ForeColor = Color.White,
                    Font = new Font("Segoe UI", 10)
                };

                DateTimePicker birthDatePicker = new DateTimePicker
                {
                    Location = new Point(150, 70),
                    Size = new Size(150, 25),
                    Format = DateTimePickerFormat.Short,
                    MaxDate = DateTime.Today
                };

                Label lblResult = new Label
                {
                    Text = "",
                    Location = new Point(20, 130),
                    Size = new Size(280, 120),
                    ForeColor = Color.FromArgb(0, 200, 83),
                    Font = new Font("Segoe UI", 12, FontStyle.Bold),
                    TextAlign = ContentAlignment.MiddleCenter
                };

                Button btnCalculate = new Button
                {
                    Text = "Calculate",
                    Location = new Point(40, 270),
                    Size = new Size(110, 40),
                    BackColor = Color.FromArgb(0, 120, 215),
                    ForeColor = Color.White,
                    FlatStyle = FlatStyle.Flat,
                    Font = new Font("Segoe UI", 11)
                };
                btnCalculate.FlatAppearance.BorderSize = 0;

                Button btnBack = new Button
                {
                    Text = "Back to Calculator",
                    Location = new Point(160, 270),
                    Size = new Size(140, 40),
                    BackColor = Color.FromArgb(60, 60, 60),
                    ForeColor = Color.White,
                    FlatStyle = FlatStyle.Flat,
                    Font = new Font("Segoe UI", 11)
                };
                btnBack.FlatAppearance.BorderSize = 0;

                btnCalculate.Click += (s, ev) =>
                {
                    DateTime birthDate = birthDatePicker.Value;
                    DateTime today = DateTime.Today;
                    int age = today.Year - birthDate.Year;
                    
                    if (birthDate.Date > today.AddYears(-age))
                        age--;

                    int months = today.Month - birthDate.Month;
                    if (months < 0)
                        months += 12;
                    
                    int days = today.Day - birthDate.Day;
                    if (days < 0)
                    {
                        months--;
                        if (months < 0)
                            months += 12;
                        days += DateTime.DaysInMonth(today.AddMonths(-1).Year, today.AddMonths(-1).Month);
                    }

                    lblResult.Text = $"Age: {age} years\n{months} months\n{days} days\n\nTotal days: {(today - birthDate).TotalDays:F0}";
                };

                btnBack.Click += (s, ev) => ShowCalculator();

                agePanel.Controls.Add(lblTitle);
                agePanel.Controls.Add(lblBirthDate);
                agePanel.Controls.Add(birthDatePicker);
                agePanel.Controls.Add(lblResult);
                agePanel.Controls.Add(btnCalculate);
                agePanel.Controls.Add(btnBack);

                Controls.Add(agePanel);
            }

            agePanel.Visible = true;
            agePanel.BringToFront();
        }

        private void CurrencyConvert_Click(object? sender, EventArgs e)
        {
            // Hide calculator
            calculatorPanel.Visible = false;
            displayPanel.Visible = false;

            // Create currency panel if it doesn't exist
            if (currencyPanel == null)
            {
                currencyPanel = new Panel
                {
                    Dock = DockStyle.Fill,
                    BackColor = Color.FromArgb(32, 32, 32)
                };

                Label lblTitle = new Label
                {
                    Text = "Currency Converter",
                    Location = new Point(20, 20),
                    Size = new Size(280, 30),
                    ForeColor = Color.White,
                    Font = new Font("Segoe UI", 16, FontStyle.Bold)
                };

                Label lblAmount = new Label
                {
                    Text = "Amount:",
                    Location = new Point(20, 70),
                    Size = new Size(80, 25),
                    ForeColor = Color.White,
                    Font = new Font("Segoe UI", 10)
                };

                TextBox txtAmount = new TextBox
                {
                    Location = new Point(110, 70),
                    Size = new Size(190, 25),
                    Font = new Font("Segoe UI", 10),
                    Text = "1"
                };

                Label lblFrom = new Label
                {
                    Text = "From:",
                    Location = new Point(20, 110),
                    Size = new Size(80, 25),
                    ForeColor = Color.White,
                    Font = new Font("Segoe UI", 10)
                };

                ComboBox cmbFrom = new ComboBox
                {
                    Location = new Point(110, 110),
                    Size = new Size(190, 25),
                    DropDownStyle = ComboBoxStyle.DropDownList,
                    Font = new Font("Segoe UI", 10)
                };

                Label lblTo = new Label
                {
                    Text = "To:",
                    Location = new Point(20, 150),
                    Size = new Size(80, 25),
                    ForeColor = Color.White,
                    Font = new Font("Segoe UI", 10)
                };

                ComboBox cmbTo = new ComboBox
                {
                    Location = new Point(110, 150),
                    Size = new Size(190, 25),
                    DropDownStyle = ComboBoxStyle.DropDownList,
                    Font = new Font("Segoe UI", 10)
                };

                // Exchange rates (1 USD = X units of currency)
                Dictionary<string, double> exchangeRates = new Dictionary<string, double>
                {
                    { "USD - US Dollar", 1.0 },
                    { "EUR - Euro", 0.92 },
                    { "GBP - British Pound", 0.79 },
                    { "JPY - Japanese Yen", 149.0 },
                    { "INR - Indian Rupee", 83.0 },
                    { "AUD - Australian Dollar", 1.52 },
                    { "CAD - Canadian Dollar", 1.36 },
                    { "CHF - Swiss Franc", 0.88 },
                    { "CNY - Chinese Yuan", 7.24 },
                    { "SEK - Swedish Krona", 10.50 },
                    { "TZS - Tanzanian Shilling", 2580.0 }
                };

                foreach (var currency in exchangeRates.Keys)
                {
                    cmbFrom.Items.Add(currency);
                    cmbTo.Items.Add(currency);
                }

                cmbFrom.SelectedIndex = 0;
                cmbTo.SelectedIndex = 1;

                Label lblResult = new Label
                {
                    Text = "",
                    Location = new Point(20, 210),
                    Size = new Size(280, 80),
                    ForeColor = Color.FromArgb(0, 200, 83),
                    Font = new Font("Segoe UI", 14, FontStyle.Bold),
                    TextAlign = ContentAlignment.MiddleCenter
                };

                Button btnConvert = new Button
                {
                    Text = "Convert",
                    Location = new Point(60, 310),
                    Size = new Size(90, 40),
                    BackColor = Color.FromArgb(0, 120, 215),
                    ForeColor = Color.White,
                    FlatStyle = FlatStyle.Flat,
                    Font = new Font("Segoe UI", 11)
                };
                btnConvert.FlatAppearance.BorderSize = 0;

                Button btnBack = new Button
                {
                    Text = "Back to Calculator",
                    Location = new Point(160, 310),
                    Size = new Size(140, 40),
                    BackColor = Color.FromArgb(60, 60, 60),
                    ForeColor = Color.White,
                    FlatStyle = FlatStyle.Flat,
                    Font = new Font("Segoe UI", 11)
                };
                btnBack.FlatAppearance.BorderSize = 0;

                btnConvert.Click += (s, ev) =>
                {
                    if (double.TryParse(txtAmount.Text, out double amount))
                    {
                        string? fromCurrency = cmbFrom.SelectedItem?.ToString();
                        string? toCurrency = cmbTo.SelectedItem?.ToString();

                        if (fromCurrency == null || toCurrency == null) return;

                        double fromRate = exchangeRates[fromCurrency];
                        double toRate = exchangeRates[toCurrency];

                        // Convert to USD first, then to target currency
                        double amountInUSD = amount / fromRate;
                        double convertedAmount = amountInUSD * toRate;

                        lblResult.Text = $"{amount:F2} {fromCurrency.Split('-')[0].Trim()}\n=\n{convertedAmount:F2} {toCurrency.Split('-')[0].Trim()}";
                    }
                    else
                    {
                        MessageBox.Show("Please enter a valid amount", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                };

                btnBack.Click += (s, ev) => ShowCalculator();

                currencyPanel.Controls.Add(lblTitle);
                currencyPanel.Controls.Add(lblAmount);
                currencyPanel.Controls.Add(txtAmount);
                currencyPanel.Controls.Add(lblFrom);
                currencyPanel.Controls.Add(cmbFrom);
                currencyPanel.Controls.Add(lblTo);
                currencyPanel.Controls.Add(cmbTo);
                currencyPanel.Controls.Add(lblResult);
                currencyPanel.Controls.Add(btnConvert);
                currencyPanel.Controls.Add(btnBack);

                Controls.Add(currencyPanel);
            }

            currencyPanel.Visible = true;
            currencyPanel.BringToFront();
        }

        private void ShowCalculator()
        {
            if (currencyPanel != null) currencyPanel.Visible = false;
            if (agePanel != null) agePanel.Visible = false;
            calculatorPanel.Visible = true;
            displayPanel.Visible = true;
        }

        private void Form1_KeyDown(object? sender, KeyEventArgs e)
        {
            // Prevent arrow keys and Tab from navigating away
            if (e.KeyCode == Keys.Up || e.KeyCode == Keys.Down || 
                e.KeyCode == Keys.Left || e.KeyCode == Keys.Right || 
                e.KeyCode == Keys.Tab)
            {
                e.Handled = true;
                return;
            }

            // Handle number keys
            if (e.KeyCode >= Keys.D0 && e.KeyCode <= Keys.D9)
            {
                char num = (char)('0' + (e.KeyCode - Keys.D0));
                SimulateButtonClick(num.ToString());
                e.Handled = true;
            }
            // Handle numpad numbers
            else if (e.KeyCode >= Keys.NumPad0 && e.KeyCode <= Keys.NumPad9)
            {
                char num = (char)('0' + (e.KeyCode - Keys.NumPad0));
                SimulateButtonClick(num.ToString());
                e.Handled = true;
            }
            // Handle operations
            else if (e.KeyCode == Keys.Add)
            {
                SimulateButtonClick("+");
                e.Handled = true;
            }
            else if (e.KeyCode == Keys.Subtract)
            {
                SimulateButtonClick("−");
                e.Handled = true;
            }
            else if (e.KeyCode == Keys.Multiply)
            {
                SimulateButtonClick("×");
                e.Handled = true;
            }
            else if (e.KeyCode == Keys.Divide)
            {
                SimulateButtonClick("÷");
                e.Handled = true;
            }
            // Handle decimal point
            else if (e.KeyCode == Keys.Decimal)
            {
                SimulateButtonClick(".");
                e.Handled = true;
            }
            // Handle equals
            else if (e.KeyCode == Keys.Return)
            {
                SimulateButtonClick("=");
                e.Handled = true;
            }
            // Handle backspace
            else if (e.KeyCode == Keys.Back)
            {
                SimulateButtonClick("⌫");
                e.Handled = true;
            }
            // Handle Delete (clear entry)
            else if (e.KeyCode == Keys.Delete)
            {
                SimulateButtonClick("CE");
                e.Handled = true;
            }
            // Handle Escape (clear all)
            else if (e.KeyCode == Keys.Escape)
            {
                SimulateButtonClick("C");
                e.Handled = true;
            }
        }

        private void SimulateButtonClick(string buttonValue)
        {
            // Find and click the button with matching tag
            foreach (Control ctrl in this.Controls)
            {
                if (ctrl is TableLayoutPanel panel)
                {
                    foreach (Control btn in panel.Controls)
                    {
                        if (btn is Button button && button.Tag?.ToString() == buttonValue)
                        {
                            button.PerformClick();
                            return;
                        }
                    }
                }
            }
        }
    }
}
