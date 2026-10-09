# Diagramas de Atividades — Sistema de Telemedicina

**Data:** 2026-10-09
**Formato:** Mermaid
**Status:** Implementado (Fase 5)

---

## AT-01 — Fluxo de Agendamento de Consulta

```mermaid
flowchart TD
    Start([Paciente acessa agendamento]) --> SelectSpecialty[Seleciona especialidade]
    SelectSpecialty --> SearchDoctors[Busca médicos disponíveis]
    SearchDoctors --> HasDoctors{Médicos disponíveis?}
    HasDoctors -->|Não| NoDoctor[Exibe mensagem: sem disponibilidade]
    NoDoctor --> End1([Fim])
    HasDoctors -->|Sim| SelectDoctor[Seleciona médico]
    SelectDoctor --> SelectDate[Seleciona data]
    SelectDate --> LoadSlots[Carrega slots disponíveis]
    LoadSlots --> HasSlots{Slots disponíveis?}
    HasSlots -->|Não| ChooseOther[Escolher outra data?]
    ChooseOther -->|Sim| SelectDate
    ChooseOther -->|Não| End2([Fim])
    HasSlots -->|Sim| SelectSlot[Seleciona horário]
    SelectSlot --> Confirm[Confirma agendamento]
    Confirm --> TryBook[Sistema tenta reservar slot]
    TryBook --> SlotFree{Slot ainda disponível?}
    SlotFree -->|Não - conflito| Conflict[Exibe erro: horário ocupado]
    Conflict --> LoadSlots
    SlotFree -->|Sim| CreateAppt[Cria consulta com status 'agendada']
    CreateAppt --> NotifyQueue[Enfileira notificações]
    NotifyQueue --> SendEmails[Envia e-mails ao paciente e médico]
    SendEmails --> ShowConfirm[Exibe confirmação ao paciente]
    ShowConfirm --> End3([Fim])
```

---

## AT-02 — Fluxo de Realização de Consulta

```mermaid
flowchart TD
    Start([Médico acessa agenda]) --> ViewList[Visualiza consultas do dia]
    ViewList --> SelectAppt[Seleciona consulta]
    SelectAppt --> CheckTime{Dentro da janela de horário?}
    CheckTime -->|Não| WaitOrSkip[Aguardar ou registrar ausência]
    WaitOrSkip --> MarkAbsent{Paciente ausente?}
    MarkAbsent -->|Sim| SetAbsent[Status: paciente_ausente]
    SetAbsent --> Audit1[Registra auditoria]
    Audit1 --> End1([Fim])
    MarkAbsent -->|Não| WaitOrSkip
    CheckTime -->|Sim| StartConsult[Inicia consulta - status: em_andamento]
    StartConsult --> Audit2[Registra auditoria]
    Audit2 --> Attend[Realiza atendimento]
    Attend --> TakeNotes[Registra anotações clínicas]
    TakeNotes --> MoreNotes{Mais informações?}
    MoreNotes -->|Sim| TakeNotes
    MoreNotes -->|Não| EndConsult[Encerra consulta]
    EndConsult --> SetStatus{Desfecho}
    SetStatus -->|Concluída| SetConcluida[Status: concluida]
    SetConcluida --> Audit3[Registra auditoria]
    Audit3 --> End2([Fim])
```

---

## AT-03 — Fluxo de Cancelamento

```mermaid
flowchart TD
    Start([Usuário solicita cancelamento]) --> IdentifyActor{Quem cancela?}
    IdentifyActor -->|Paciente| CheckDeadline{Dentro do prazo?}
    CheckDeadline -->|Não| RefusePaciente[Recusa: prazo expirado]
    RefusePaciente --> End1([Fim])
    CheckDeadline -->|Sim| CheckStatusP{Status permite cancelamento?}
    IdentifyActor -->|Médico ou Admin| CheckStatusA{Status permite cancelamento?}
    CheckStatusP -->|Não| RefuseStatus[Recusa: status inválido]
    CheckStatusA -->|Não| RefuseStatus
    RefuseStatus --> End2([Fim])
    CheckStatusP -->|Sim| CancelAppt[Cancela consulta]
    CheckStatusA -->|Sim| CancelAppt
    CancelAppt --> UpdateStatus[Status: cancelada + motivo + cancelled_by]
    UpdateStatus --> FreeSlot[Libera slot de disponibilidade]
    FreeSlot --> Audit[Registra auditoria]
    Audit --> NotifyQueue[Enfileira notificações]
    NotifyQueue --> SendEmails[Envia e-mails ao paciente e médico]
    SendEmails --> End3([Fim])
```
