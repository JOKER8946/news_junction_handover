-- =============================================================================
-- Manikya Market v7 — support tickets
-- =============================================================================
-- Buyers raise tickets on their orders. The merchant owning that order can
-- reply. Super-admin can also reply (mediation) and sees every ticket on the
-- platform.
-- =============================================================================

CREATE TABLE IF NOT EXISTS tickets (
  id         INT NOT NULL AUTO_INCREMENT,
  order_id   INT NULL,
  buyer_id   INT NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  status     ENUM('open','pending','resolved','escalated') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_tickets_buyer (buyer_id),
  INDEX idx_tickets_order (order_id),
  INDEX idx_tickets_status (status),
  CONSTRAINT fk_tickets_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tickets_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS ticket_messages (
  id          INT NOT NULL AUTO_INCREMENT,
  ticket_id   INT NOT NULL,
  sender_type ENUM('buyer','merchant','super_admin') NOT NULL,
  sender_id   INT NOT NULL,
  message     TEXT NOT NULL,
  image_path  VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_ticket_messages_ticket (ticket_id),
  CONSTRAINT fk_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
