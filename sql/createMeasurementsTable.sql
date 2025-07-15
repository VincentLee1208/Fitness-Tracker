CREATE TABLE Measurements (
    user_id NUMBER,
    m_date DATE,
    m_weight NUMBER,
    m_height NUMBER,
    m_BMI NUMBER,
    PRIMARY KEY (user_id, m_date),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

INSERT INTO Measurements (user_id, m_date, m_weight, m_height, m_BMI) 
VALUES (1, TO_DATE('2023-11-07', 'YYYY-MM-DD'), 55, 172, 27);