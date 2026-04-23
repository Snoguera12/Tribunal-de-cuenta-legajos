from django.db import models

# Create your models here.

class Legajo(models.Model):
    n_legajo = models.CharField(max_length=50, null=False, unique=True,verbose_name="Número de Legajo")
    nombre = models.CharField(max_length=50, verbose_name="Nombre")
    dni = models.CharField(max_length=16, verbose_name="DNI")
    cargo = models.CharField(max_length=30, verbose_name="Cargo")
    estado = models.BooleanField(default=True, verbose_name="Estado")
    create_date = models.DateTimeField(null=True, verbose_name="creada")
    update_date = models.DateTimeField(null=True, verbose_name="actualizada")
    
    class Meta:
        verbose_name = "Legajo"
        verbose_name_plural = "Legajos"

    def __str__(self):
        return self.name
    